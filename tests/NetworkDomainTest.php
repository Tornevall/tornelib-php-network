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
