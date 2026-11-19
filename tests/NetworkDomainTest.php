<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Module\Exception\DomainException;
use TorneLIB\Module\Network\Domain;

class NetworkDomainTest extends TestCase
{
    public function testParseUrlWithSchemeAndPath()
    {
        self::assertSame(
            ['www.example.com', 'https', '/a/b'],
            (new Domain())->getUrlDomain('https://www.example.com/a/b?query=yes')
        );
    }

    public function testParseHostnameWithoutScheme()
    {
        self::assertSame(
            ['example.com', 'http', null],
            (new Domain())->getUrlDomain('example.com')
        );
    }

    public function testSuccessfulHostValidationUsesDeterministicDnsFixture()
    {
        $domain = new class extends Domain {
            protected function resolveDnsRecords(string $hostname)
            {
                return [['type' => 'A', 'host' => $hostname, 'ip' => '192.0.2.10']];
            }
        };

        self::assertSame(
            ['www.example.test', 'https', '/path'],
            $domain->getUrlDomain('https://www.example.test/path', true)
        );
    }

    public function testMissingDnsRecordsAreRejectedDeterministically()
    {
        $domain = new class extends Domain {
            protected function resolveDnsRecords(string $hostname)
            {
                return [];
            }
        };

        $this->expectException(DomainException::class);
        $domain->getUrlDomain('https://www.example.test/path', true);
    }

    public function testInvalidHostValidationThrowsLibraryExceptionWithoutDnsRequest()
    {
        $this->expectException(DomainException::class);
        (new Domain())->getUrlDomain('http://invalid host', true);
    }

    public static function registrableDomainCases()
    {
        return [
            ['https://www.example.com/one', 'example.com'],
            ['https://www.example.co.uk/two', 'example.co.uk'],
            ['https://www.example.com.au/three', 'example.com.au'],
            ['a.b.example.com', 'example.com'],
            ['www.example.co.za', 'example.co.za'],
            ['foo.uk.com', 'foo.uk.com'],
            ['sub.foo.uk.com', 'foo.uk.com'],
            ['alice.github.io', 'alice.github.io'],
            ['one.alice.github.io', 'alice.github.io'],
            ['github.io', ''],
            ['bar.blogspot.com', 'bar.blogspot.com'],
            ['bar.s3.amazonaws.com', 'bar.s3.amazonaws.com'],
            ['s3.amazonaws.com', ''],
            ['a.b.ck', 'a.b.ck'],
            ['a.ck', ''],
            ['www.ck', 'www.ck'],
            ['a.www.ck', 'www.ck'],
            ['a.city.kawasaki.jp', 'city.kawasaki.jp'],
            ['www.a.kawasaki.jp', 'www.a.kawasaki.jp'],
            ['a.kawasaki.jp', ''],
            ['https://u:p@www.city.kawasaki.jp:8443/path', 'city.kawasaki.jp'],
            ['sub.xn--aroport-bya.ci', 'sub.xn--aroport-bya.ci'],
            ['xn--aroport-bya.ci', ''],
            ['FOO.Example.COM.', 'example.com'],
            ['www.example.co.uk:443', 'example.co.uk'],
            ['example.unknown-tld', 'example.unknown-tld'],
            ['co.uk', ''],
            ['com', ''],
            ['www.example.com..', ''],
            ['-bad.example.com', ''],
            ['good..example.com', ''],
            ['http://[::1]:8080', ''],
            ['example.com:999999', ''],
            ['localhost', ''],
            ['127.0.0.1', ''],
        ];
    }

    /**
     * @dataProvider registrableDomainCases
     */
    public function testRegistrableDomainOrEmptyString($host, $expected)
    {
        self::assertSame($expected, (new Domain())->getDomainName($host));
    }

    public function testCompletePublicSuffixSnapshotIsBundled()
    {
        $file = __DIR__ . '/../resources/public_suffix_list.dat';
        self::assertFileExists($file);
        $content = file_get_contents($file);
        self::assertTrue(is_string($content));
        self::assertTrue(strpos($content, '// ===BEGIN ICANN DOMAINS===') !== false);
        self::assertTrue(strpos($content, '// ===BEGIN PRIVATE DOMAINS===') !== false);
        self::assertTrue(strpos($content, '*.ck') !== false);
        self::assertTrue(strpos($content, '!www.ck') !== false);
        self::assertTrue(strpos($content, 'github.io') !== false);
        self::assertGreaterThan(100000, strlen($content));
    }

    public function testUnicodeHostIsConvertedToAsciiOrFailsClosedWithoutIntl()
    {
        $result = (new Domain())->getDomainName('https://shop.aéroport.ci/a');
        if (function_exists('idn_to_ascii')) {
            self::assertSame('shop.xn--aroport-bya.ci', $result);
        } else {
            self::assertSame('', $result);
        }
    }

    public function testEmptyInputUsesCurrentHttpHost()
    {
        $original = $_SERVER;
        try {
            $_SERVER['HTTP_HOST'] = 'www.example.co.uk:443';
            self::assertSame('example.co.uk', (new Domain())->getDomainName(''));
        } finally {
            $_SERVER = $original;
        }
    }

    public function testExtractQuotedUrlsAndDeduplicate()
    {
        $html = '<a href="http://a.example/1">one</a>'
            . "<a href='https://b.example/2'>two</a>"
            . '<a href="http://a.example/1">repeat</a>';
        self::assertSame(
            ['http://a.example/1', 'https://b.example/2'],
            (new Domain())->getUrlsFromHtml($html)
        );
    }

    public function testCustomProtocolIsSupported()
    {
        $html = '<a href="ftp://files.example/a">a</a>'
            . '<a href="https://www.example.com/">b</a>';

        self::assertSame(
            ['ftp://files.example/a'],
            (new Domain())->getUrlsFromHtml($html, -1, -1, ['ftp'])
        );
    }

    public function testUrlLimitAppliesAtDefaultOffset()
    {
        $html = '<a href="http://a.example/">a</a><a href="http://b.example/">b</a>';
        self::assertSame(
            ['http://a.example/'],
            (new Domain())->getUrlsFromHtml($html, -1, 1)
        );
    }

    public function testOffsetAndLimitCanBeCombined()
    {
        $html = '<a href="http://a.example/">a</a>'
            . '<a href="http://b.example/">b</a>'
            . '<a href="http://c.example/">c</a>';

        self::assertSame(
            ['http://b.example/'],
            (new Domain())->getUrlsFromHtml($html, 1, 1)
        );
    }

    public function testZeroLimitAndOutOfBoundsOffsetReturnEmptyArray()
    {
        $html = '<a href="http://a.example/">a</a>';
        $domain = new Domain();

        self::assertSame([], $domain->getUrlsFromHtml($html, -1, 0));
        self::assertSame([], $domain->getUrlsFromHtml($html, 4, 1));
    }
}
