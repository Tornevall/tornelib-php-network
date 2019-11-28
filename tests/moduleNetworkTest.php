<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\MODULE_NETWORK;

class moduleNetworkTest extends TestCase
{
    /**
     * @var MODULE_NETWORK
     */
    private $NETWORK;

    public function setUp()
    {
        $this->NETWORK = new TorneLIB\MODULE_NETWORK();
    }

    /**
     * @test
     */
    public function testGetProxyData()
    {
        $_SERVER['HTTP_VIA'] = 'test-suite.localhost ';
        $LOCAL = new TorneLIB\MODULE_NETWORK();
        static::assertTrue(count($LOCAL->getProxyData()) > 0 ? true : false);
    }
}
