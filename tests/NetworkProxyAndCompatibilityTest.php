<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Module\Network;
use TorneLIB\Module\Network\Proxy;
use TorneLIB\Module\Network\Statics;
use TorneLIB\MODULE_NETWORK;

class NetworkProxyAndCompatibilityTest extends TestCase
{
    private function withServerValues(array $values, callable $run)
    {
        $original = $_SERVER;
        try {
            foreach ((new Proxy())->getProxyHeaders() as $key) {
                unset($_SERVER[$key]);
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

    public function testProxyHeaderEnumerationIncludesConfiguredKeys()
    {
        $this->withServerValues(['HTTP_VIA' => 'gateway', 'HTTP_X_FORWARDED_FOR' => '192.0.2.1'], function () {
            $proxy = new Proxy();
            self::assertCount(15, $proxy->getProxyHeaders());
            self::assertSame(
                ['HTTP_VIA' => 'gateway', 'HTTP_X_FORWARDED_FOR' => '192.0.2.1'],
                $proxy->getProxyData()
            );
            self::assertCount(15, $proxy->getProxyData(false));
            self::assertArrayHasKey('HTTP_FORWARDED', $proxy->getProxyData(false));
            self::assertNull($proxy->getProxyData(false)['HTTP_FORWARDED']);
        });
    }

    public function testProxyHeadersAreASnapshotAtConstruction()
    {
        $this->withServerValues(['HTTP_VIA' => 'first'], function () {
            $proxy = new Proxy();
            $_SERVER['HTTP_VIA'] = 'second';

            self::assertSame('first', $proxy->getProxyData()['HTTP_VIA']);
            self::assertSame('second', (new Proxy())->getProxyData()['HTTP_VIA']);
        });
    }

    public function testNetworkFacadeForwardsProxyAndAddressMethods()
    {
        $this->withServerValues(['HTTP_VIA' => 'gateway'], function () {
            $network = new Network();

            self::assertSame(['HTTP_VIA' => 'gateway'], $network->getProxyData());
            self::assertCount(15, $network->getProxyHeaders());
            self::assertSame('1.0.0.127', $network->getArpaFromIpv4('127.0.0.1'));
        });
    }

    public function testLegacyFacadeRetainsPublicCompatibility()
    {
        $legacy = new MODULE_NETWORK();

        self::assertTrue($legacy->isDeprecated);
        self::assertSame('1.0.0.127', $legacy->getArpaFromIpv4('127.0.0.1'));
        self::assertCount(15, $legacy->getProxyHeaders());
    }

    public function testStaticFallbackRetainsAddressLookup()
    {
        self::assertSame('1.0.0.127', Statics::getArpaFromIpv4('127.0.0.1'));
    }

    public function testUnknownFacadeMethodThrowsLibraryException()
    {
        $this->expectException(ExceptionHandler::class);
        (new Network())->unavailableNetworkMethod();
    }

    public function testCookieStateSurvivesDynamicFacadeCalls()
    {
        $network = new Network();
        $network->setCookieDefaultPath('/through-facade');
        $network->setCookieDefaultPrefix('facade_');

        self::assertSame('/through-facade', $network->getCookieDefaultPath());
        self::assertSame('facade_', $network->getCookieDefaultPrefix());
    }

    public function testCookieStateSurvivesLegacyFacadeCalls()
    {
        $legacy = new MODULE_NETWORK();
        $legacy->setCookieDefaultPath('/legacy');

        self::assertSame('/legacy', $legacy->getCookieDefaultPath());
    }

    public function testHostFallbackIsDeterministic()
    {
        $this->withServerValues(['HTTP_HOST' => null], function () {
            self::assertSame('localhost', (new Network())->getHttpHost());
        });
        $this->withServerValues(['HTTP_HOST' => 'www.example.test:443'], function () {
            self::assertSame('www.example.test:443', (new Network())->getHttpHost());
        });
    }
}
