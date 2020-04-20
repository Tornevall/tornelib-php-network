<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\IO\Data\Strings;
use TorneLIB\Module\Network;
use TorneLIB\MODULE_NETWORK;

class genericTest extends TestCase
{
    /**
     * @test Get Proxy data from network client.
     */
    public function getProxyData()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_VIA'] = '127.0.0.1';
        static::assertCount(1, (new Network())->getProxyData());
    }

    /**
     * @test Get set proxy headers.
     */
    public function getProxyHeaders()
    {
        static::assertCount(15, (new Network())->getProxyHeaders());
    }

    /**
     * @test
     * What happens when obsolete or removed methods are accessed.
     */
    public function getObsoleteMethod()
    {
        try {
            (new Network())->getWhatDoesNotExist();
        } catch (ExceptionHandler $e) {
            static::assertTrue(
                $e->getCode() === Constants::LIB_METHOD_OR_LIBRARY_UNAVAILABLE
            );
        }
    }

    /**
     * @test
     */
    public function getDeprecatedMethod()
    {
        static::assertTrue((new Network())->getCurrentServerProtocol(true) === 'http');
    }

    /**
     * @test Test backward compatibility.
     */
    public function deprecatedModuleFunc()
    {
        static::assertCount(15, (new MODULE_NETWORK())->getProxyHeaders());
    }

    /**
     * @test Test backward compatibility.
     */
    public function deprecatedModuleVar()
    {
        static::assertTrue((new MODULE_NETWORK())->isDeprecated);
    }

    /**
     * @test Test unexistent variables and backward compatiblity.
     */
    public function deprecatedUnexistentModuleVar()
    {
        try {
            $var = (new MODULE_NETWORK())->thisDoesNotExist;
        } catch (\Exception $e) {
            static::assertTrue(
                $e->getCode() === Constants::LIB_METHOD_OR_LIBRARY_UNAVAILABLE
            );
        }
    }

    /**
     * @test
     */
    public function getDeprecatedHttps()
    {
        $_SERVER['HTTPS'] = true;
        static::assertTrue((new Network())->isSecureHttp());
        unset($_SERVER['HTTPS']);
    }

    /**
     * @test
     */
    public function getIsSecureHttp()
    {
        $_SERVER['HTTPS'] = true;
        static::assertTrue((new Network())->getIsSecureHttp());
        unset($_SERVER['HTTPS']);
    }

    /**
     * @test
     */
    public function getProtocol()
    {
        static::assertTrue((new Network())->getProtocol() === 'http');
    }

    /**
     * @test
     */
    public function getHttpHost()
    {
        static::assertTrue((new Network())->getHttpHost() === 'localhost');
    }

    /**
     * Convert snakecases to camelcase.
     *
     * @test
     */
    public function getCamelCased()
    {
        static::assertTrue(Strings::returnCamelCase('base64url_encode') === "base64urlEncode");
    }

    /**
     * @test
     */
    public function testDeprecatedSnakes()
    {
        $encodedString = (new MODULE_NETWORK())->base64url_encode('TEST');
        static::assertTrue($encodedString === 'VEVTVA');
    }

    /**
     * @test
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function getGitTagsNetcurl()
    {
        if (!class_exists('TorneLIB\Helpers\NetUtil')) {
            static::markTestSkipped('TorneLIB\Helpers\NetUtil unavailable');
            return;
        }

        static::assertGreaterThan(
            2,
            (new Network())->getGitTagsByUrl("https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git")
        );
    }

    /**
     * @test
     */
    public function getGitTagsNetcurlBucket()
    {
        if (!class_exists('TorneLIB\Helpers\NetUtil')) {
            static::markTestSkipped('TorneLIB\Helpers\NetUtil unavailable');
            return;
        }
        static::assertGreaterThan(
            2,
            (new TorneLIB\Helpers\NetUtil())->getGitTagsByUrl("https://bitbucket.org/resursbankplugins/resurs-ecomphp/src/master/")
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getGitOldUtil() {
        if (!class_exists('TorneLIB\Helpers\NetUtil')) {
            static::markTestSkipped('TorneLIB\Helpers\NetUtil unavailable');
            return;
        }

        static::assertGreaterThan(
            2,
            (new Network())->getGitTagsByUrl("https://bitbucket.org/resursbankplugins/resurs-ecomphp/src/master/")
        );
    }
}
