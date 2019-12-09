<?php

require_once(__DIR__ . '/../vendor/autoload.php');
use TorneLIB\MODULE_NETWORK;

class moduleNetworkTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MODULE_NETWORK
     */
    private $NETWORK;

    public function setUp()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_VIA'] = '127.0.0.1';
        $this->NETWORK = new MODULE_NETWORK();
    }

    /**
     * @test
     */
    public function testGetProxyData()
    {
        $GP = $this->NETWORK->getProxyData();
        static::assertTrue(isset($GP['HTTP_VIA']));
    }
}
