<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\IO\Data\Strings;
use TorneLIB\Module\Exception\AddressException;
use TorneLIB\Module\Exception\DomainException;
use TorneLIB\Module\Network;
use TorneLIB\Module\Network\Address;
use TorneLIB\Module\Network\Domain;
use TorneLIB\Module\Network\Statics;
use TorneLIB\MODULE_NETWORK;

class genericTest extends TestCase
{
    /**
     * Restore all request-global values even when an assertion fails.
     *
     * @param array $values
     * @param callable $run
     */
    private function withServerValues(array $values, callable $run)
    {
        $original = $_SERVER;
        try {
            foreach ((new \TorneLIB\Module\Network\Proxy())->getProxyHeaders() as $header) {
                unset($_SERVER[$header]);
            }
            foreach ($values as $key => $value) {
                if ($value === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $value;
                }
            }
            $run();
        } finally {
            $_SERVER = $original;
        }
    }

    /**
     * Get Proxy data from network client.
     */
    public function testGetProxyData()
    {
        $this->withServerValues(
            ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_VIA' => '127.0.0.1'],
            function () {
                static::assertCount(1, (new Network())->getProxyData());
            }
        );
    }

    /**
     * Get set proxy headers.
     */
    public function testGetProxyHeaders()
    {
        static::assertCount(15, (new Network())->getProxyHeaders());
    }

    /**
     * * What happens when obsolete or removed methods are accessed.
     */
    public function testGetObsoleteMethod()
    {
        $this->expectException(ExceptionHandler::class);
        $this->expectExceptionCode(Constants::LIB_METHOD_OR_LIBRARY_UNAVAILABLE);
        (new Network())->getWhatDoesNotExist();
    }

    /**
     * */
    public function testGetDeprecatedMethod()
    {
        static::assertTrue((new Network())->getCurrentServerProtocol(true) === 'http');
    }

    /**
     * Test backward compatibility.
     */
    public function testDeprecatedModuleFunc()
    {
        static::assertCount(15, (new MODULE_NETWORK())->getProxyHeaders());
    }

    /**
     * Test backward compatibility.
     */
    public function testDeprecatedModuleVar()
    {
        static::assertTrue((new MODULE_NETWORK())->isDeprecated);
    }

    /**
     * Test unexistent variables and backward compatiblity.
     */
    public function testDeprecatedUnexistentModuleVar()
    {
        $this->expectException(ExceptionHandler::class);
        $this->expectExceptionCode(Constants::LIB_METHOD_OR_LIBRARY_UNAVAILABLE);
        (new MODULE_NETWORK())->thisDoesNotExist;
    }

    /**
     * */
    public function testGetDeprecatedHttps()
    {
        $this->withServerValues(['HTTPS' => true], function () {
            static::assertTrue((new Network())->isSecureHttp());
        });
    }

    /**
     * */
    public function testGetIsSecureHttp()
    {
        $this->withServerValues(['HTTPS' => true], function () {
            static::assertTrue((new Network())->getIsSecureHttp());
        });
    }

    /**
     * */
    public function testGetProtocol()
    {
        $this->withServerValues(['HTTPS' => null], function () {
            static::assertSame('http', (new Network())->getProtocol());
        });
    }

    /**
     * */
    public function testGetHttpHost()
    {
        $this->withServerValues(['HTTP_HOST' => null], function () {
            static::assertSame('localhost', (new Network())->getHttpHost());
        });
    }

    /**
     * Convert snakecases to camelcase.
     *
     * */
    public function testGetCamelCased()
    {
        static::assertTrue(Strings::returnCamelCase('base64url_encode') === "base64urlEncode");
    }

    /**
     * */
    public function testDeprecatedSnakes()
    {
        $encodedString = (new MODULE_NETWORK())->base64url_encode('TEST');
        static::assertTrue($encodedString === 'VEVTVA');
    }

    /**
     * * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function testGetGitTagsNetcurl()
    {
        if (getenv('TORNELIB_NETWORK_LIVE_TESTS') !== '1') {
            static::markTestSkipped('Live network test disabled; set TORNELIB_NETWORK_LIVE_TESTS=1 to opt in.');
        }

        if (!class_exists('TorneLIB\Helpers\NetUtils')) {
            static::markTestSkipped('TorneLIB\Helpers\NetUtils unavailable');
            return;
        }

        static::assertGreaterThan(
            2,
            (new Network())->getGitTagsByUrl("https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git")
        );
    }

    /**
     * */
    public function testGetGitTagsNetcurlBucket()
    {
        if (getenv('TORNELIB_NETWORK_LIVE_TESTS') !== '1') {
            static::markTestSkipped('Live network test disabled; set TORNELIB_NETWORK_LIVE_TESTS=1 to opt in.');
        }

        if (!class_exists('TorneLIB\Helpers\NetUtils')) {
            static::markTestSkipped('TorneLIB\Helpers\NetUtils unavailable');
            return;
        }
        static::assertGreaterThan(
            2,
            (new TorneLIB\Helpers\NetUtils())->getGitTagsByUrl("https://bitbucket.org/resursbankplugins/resurs-ecomphp/src/master/")
        );
    }

    /**
     * * @throws ExceptionHandler
     */
    public function testGetGitOldUtil()
    {
        if (getenv('TORNELIB_NETWORK_LIVE_TESTS') !== '1') {
            static::markTestSkipped('Live network test disabled; set TORNELIB_NETWORK_LIVE_TESTS=1 to opt in.');
        }

        if (!class_exists('TorneLIB\Helpers\NetUtils')) {
            static::markTestSkipped('TorneLIB\Helpers\NetUtils unavailable');
            return;
        }

        static::assertGreaterThan(
            2,
            (new Network())->getGitTagsByUrl("https://bitbucket.org/resursbankplugins/resurs-ecomphp/src/master/")
        );
    }

    /**
     * * @throws ExceptionHandler
     */
    public function testGetUrlDomain()
    {
        $response = (new Domain())->getUrlDomain(
            'https://www.aftonbladet.se'
        );

        static::assertTrue($response[0] === 'www.aftonbladet.se');
    }

    /**
     * * @throws ExceptionHandler
     */
    public function testGetUrlDomainException()
    {
        static::expectException(DomainException::class);

        (new Domain())->getUrlDomain(
            'ftp:\nope.XXXXX',
            true
        );
    }

    /**
     * */
    public function testGetUrlsFromHtml()
    {
        $html = '<html>
			<a href="http://test.com/url1">URL 1</a>
			<a href=\'http://test.com/url2\'>URL 2</a>
			<a href= "http://test.com/url3" >URL 3</a>
			<a href= \'http://test.com/url4\' >URL 4</a>
			<img src="http://test.com/img1">IMG 1</a>
			<img src=\'http://test.com/img2\'>IMG 2</a>
			<img src= "http://test.com/img3" >IMG 3</a>
			<img src= \'http://test.com/img4\' >IMG 4</a>

			<a href="http://test.com/durl1">Duplicate URL 1</a>
			<a href=\'http://test.com/durl2\'>Duplicate URL 2</a>
			<a href= "http://test.com/durl3" >Duplicate URL 3</a>
			<a href= \'http://test.com/durl4\' >Duplicate URL 4</a>
			<img src="http://test.com/dimg1">Duplicate IMG 1</a>
			<img src=\'http://test.com/dimg2\'>Duplicate IMG 2</a>
			<img src= "http://test.com/dimg3" >Duplicate IMG 3</a>
			<img src= \'http://test.com/dimg4\' >Duplicate IMG 4</a>
			</html>
		';

        static::assertCount(16, (new Domain())->getUrlsFromHtml($html));
    }

    /**
     * * @throws Exception
     */
    public function testGetDomainName()
    {
        static::assertTrue(
            (new Domain())->getDomainName('https://www.aftonbladet.se/artikel/hej/hopp') === 'aftonbladet.se'
        );
    }

    /**
     * * Test if some of the internal requests are reachable from the static caller. Backward compatible.
     */
    public function testStaticInternal()
    {
        static::assertTrue(
            Statics::getArpaFromIpv4('127.0.0.1') === '1.0.0.127'
        );
    }

    /**
     * * Same as statics above but from Network directly.
     */
    public function testSecondWayRequest()
    {
        static::assertTrue(
            (new Network())->getArpaFromIpv4('127.0.0.1') === '1.0.0.127'
        );
    }

    /**
     * * Same as statics and secondWay, but from deprecated module.
     */
    public function testThirdWayRequest()
    {
        static::assertTrue(
            (new MODULE_NETWORK())->getArpaFromIpv4('127.0.0.1') === '1.0.0.127'
        );
    }

    /**
     * */
    public function testArpaRequest4()
    {
        static::assertTrue(
            (new Network())->getArpa('212.63.208.1') === '1.208.63.212'
        );
    }

    /**
     * */
    public function testArpaRequest6()
    {
        static::assertTrue(
            (new Network())->getArpa('2a01:299:a0:ff:ff:ff:ff:ff') ===
            'f.f.0.0.f.f.0.0.f.f.0.0.f.f.0.0.f.f.0.0.0.a.0.0.9.9.2.0.1.0.a.2'
        );
    }

    /**
     * */
    public function testIpType()
    {
        $t4 = Statics::getIpType('127.0.0.1');
        $t6 = Statics::getIpType('::ff');

        static::assertTrue(
            $t4 === 4 &&
            $t6 === 6
        );
    }

    /**
     * */
    public function testCidrMask()
    {
        $range = (new Address())->getRangeFromMask('127.0.0.240/28');
        static::assertCount(16, $range);
    }

    /**
     * */
    public function testArpa()
    {
        static::assertTrue(
            (new Address())->getArpa('127.0.0.1') === '1.0.0.127'
        );
    }

    /**
     * */
    public function testDomain()
    {
        $domainCheck = (new Domain())->getUrlDomain('https://www.aftonbladet.se');
        static::assertTrue(
            $domainCheck[0] === 'www.aftonbladet.se' &&
            $domainCheck[1] === 'https'
        );
    }

    /**
     * */
    public function testGetIpv6FromOctetsCatchException()
    {
        static::expectException(AddressException::class);
        (new Address())->getIpv6FromOctets('korv');
    }
}
