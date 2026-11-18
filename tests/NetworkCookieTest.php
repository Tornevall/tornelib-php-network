<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Module\Network\Cookie;

class NetworkCookieTest extends TestCase
{
    private function withServerValues(array $values, callable $run)
    {
        $original = $_SERVER;
        try {
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

    public function testExplicitCookieParametersAreRetained()
    {
        $cookie = new Cookie();
        $cookie->setCookieParameters('/application', 'app_', 'example.test', true);

        self::assertSame('/application', $cookie->getCookieDefaultPath());
        self::assertSame('app_', $cookie->getCookieDefaultPrefix());
        self::assertSame('example.test', $cookie->getCookieDefaultDomain());
        self::assertTrue($cookie->isCookieUseSecure());
    }

    public function testCookieOptionsCanBeUpdatedViaSetters()
    {
        $cookie = new Cookie();
        $cookie->setCookieDefaultPath('/new');
        $cookie->setCookieDefaultDomain('example.test');
        $cookie->setCookieDefaultPrefix('prefix_');
        $cookie->setCookieUseSecure(true);

        self::assertSame('/new', $cookie->getCookieDefaultPath());
        self::assertSame('example.test', $cookie->getCookieDefaultDomain());
        self::assertSame('prefix_', $cookie->getCookieDefaultPrefix());
        self::assertTrue($cookie->isCookieUseSecure());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSettingCookieDoesNotDiscardExplicitPath()
    {
        $this->withServerValues(['HTTP_HOST' => 'example.test'], function () {
            $cookie = new Cookie();
            $cookie->setCookieDefaultPath('/custom');
            $cookie->setCookie('key', 'value', time() + 3600);

            self::assertSame('/custom', $cookie->getCookieDefaultPath());
        });
    }

    public function testChangingDomainParameterOverridesPreviousDomain()
    {
        $cookie = new Cookie();
        $cookie->setCookieParameters('/', null, 'first.example.test');
        $cookie->setCookieParameters('/', null, 'second.example.test');

        self::assertSame('second.example.test', $cookie->getCookieDefaultDomain());
    }

    public function testDefaultDomainRemovesHttpHostPort()
    {
        $this->withServerValues(['HTTP_HOST' => 'www.example.test:8443'], function () {
            $cookie = new Cookie();
            $cookie->setCookieParameters();

            self::assertSame('.www.example.test', $cookie->getCookieDefaultDomain());
        });
    }

    public function testMissingHostAndIpHostsAvoidInvalidDomainAttributes()
    {
        foreach ([null, 'localhost', '127.0.0.1', '[::1]:8080'] as $host) {
            $this->withServerValues(['HTTP_HOST' => $host], function () {
                $cookie = new Cookie();
                $cookie->setCookieParameters();
                self::assertSame('', $cookie->getCookieDefaultDomain());
            });
        }
    }

    public function testSecureFlagRecognizesNormalHttpsServerValue()
    {
        $this->withServerValues(['HTTPS' => 'on', 'HTTP_HOST' => 'example.test'], function () {
            $cookie = new Cookie();
            $cookie->setCookieParameters();

            self::assertTrue($cookie->isCookieUseSecure());
        });
    }

    public function testExplicitSecureFalseIsHonored()
    {
        $this->withServerValues(['HTTPS' => 'on', 'HTTP_HOST' => 'example.test'], function () {
            $cookie = new Cookie();
            $cookie->setCookieParameters('/', null, null, false);

            self::assertFalse($cookie->isCookieUseSecure());
        });
    }
}
