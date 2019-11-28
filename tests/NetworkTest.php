<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\IO\Data\Strings;
use TorneLIB\Module\Network;

class NetworkTest extends TestCase
{
    /**
     * @var Network
     */
    private $NETWORK;

    /**
     * @var \TorneLIB\MODULE_NETWORK
     */
    private $ModNet;

    public function setUp()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_VIA'] = '127.0.0.1';
        $this->NETWORK = new Network();
        $this->ModNet = new \TorneLIB\MODULE_NETWORK();
    }

    /**
     * @test Get Proxy data from network client.
     */
    public function getProxyData()
    {
        static::assertCount(1, $this->NETWORK->getProxyData());
    }

    /**
     * @test Get set proxy headers.
     */
    public function getProxyHeaders()
    {
        static::assertCount(15, $this->NETWORK->getProxyHeaders());
    }

    /**
     * @test What happens when obsolete or removed methods are accessed.
     */
    public function getObsoleteMethod()
    {
        try {
            $this->NETWORK->getWhatDoesNotExist();
        } catch (Exception $e) {
            static::assertTrue($e->getCode() === 1);
        }
    }

    /**
     * @test
     */
    public function getDeprecatedMethod()
    {
        static::assertTrue($this->NETWORK->getCurrentServerProtocol(true) === 'http');
    }

    /**
     * @test Test backward compatibility.
     */
    public function deprecatedModuleFunc()
    {
        static::assertCount(15, $this->ModNet->getProxyHeaders());
    }

    /**
     * @test Test backward compatibility.
     */
    public function deprecatedModuleVar()
    {
        static::assertTrue($this->ModNet->isDeprecated);
    }

    /**
     * @test Test unexistent variables and backward compatiblity.
     */
    public function deprecatedUnexistentModuleVar()
    {
        try {
            $var = $this->ModNet->thisDoesNotExist;
        } catch (\Exception $e) {
            static::assertTrue($e->getCode() === 1);
        }
    }

    /**
     * @test
     */
    public function getDeprecatedHttps()
    {
        $_SERVER['HTTPS'] = true;
        static::assertTrue($this->NETWORK->isSecureHttp());
        unset($_SERVER['HTTPS']);
    }

    /**
     * @test
     */
    public function getIsSecureHttp()
    {
        $_SERVER['HTTPS'] = true;
        static::assertTrue($this->NETWORK->getIsSecureHttp());
        unset($_SERVER['HTTPS']);
    }

    /**
     * @test
     */
    public function getProtocol()
    {
        static::assertTrue($this->NETWORK->getProtocol() === 'http');
    }

    /**
     * @test
     */
    public function getHttpHost()
    {
        static::assertTrue($this->NETWORK->getHttpHost() === 'localhost');
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
        $encodedString = $this->ModNet->base64url_encode('TEST');
        static::assertTrue($encodedString === 'VEVTVA');
    }
}
