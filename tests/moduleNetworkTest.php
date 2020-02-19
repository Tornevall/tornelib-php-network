<?php

require_once(__DIR__ . '/../vendor/autoload.php');
use PHPUnit\Framework\TestCase;
use TorneLIB\MODULE_NETWORK;

class moduleNetworkTest extends TestCase
{
    /**
     * @test
     */
    public function testGetProxyData()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_VIA'] = '127.0.0.1';
        $LOCAL = new TorneLIB\MODULE_NETWORK();
        static::assertTrue(count($LOCAL->getProxyData()) > 0 ? true : false);
    }
}
