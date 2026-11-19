<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\Module\Network\Proxy;

class moduleNetworkTest extends TestCase
{
    public function testGetProxyData()
    {
        $original = $_SERVER;
        try {
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
            $_SERVER['HTTP_VIA'] = '127.0.0.1';

            $proxy = new Proxy();
            self::assertSame('127.0.0.1', $proxy->getProxyData()['HTTP_VIA']);
        } finally {
            $_SERVER = $original;
        }
    }

    public function testEmptyProxyHeadersAreFilteredOut()
    {
        $original = $_SERVER;
        try {
            foreach ((new Proxy())->getProxyHeaders() as $key) {
                unset($_SERVER[$key]);
            }

            $proxy = new Proxy();
            self::assertSame([], $proxy->getProxyData());
            self::assertCount(15, $proxy->getProxyData(false));
        } finally {
            $_SERVER = $original;
        }
    }
}
