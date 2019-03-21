<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use TorneLIB\Module\Network;
use PHPUnit\Framework\TestCase;

class NetworkTest extends TestCase
{
    /**
     * @var MODULE_NETWORK
     */
    private $NETWORK;

    public function setUp()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_VIA'] = '127.0.0.1';
        $this->NETWORK = new Network();
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
        $result = $this->NETWORK->getProxyHeaders();

        static::assertCount(15, $result);
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
}
