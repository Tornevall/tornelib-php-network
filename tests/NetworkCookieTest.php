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

    public function testAutomaticDomainRejectsPublicSuffixAndMalformedHosts()
    {
        $cases = [
            ['co.uk', ''],
            ['github.io', ''],
            ['alice.github.io', '.alice.github.io'],
            ['www.example.co.uk:8443', '.www.example.co.uk'],
            ['shop.example.test', '.shop.example.test'],
            ['invalid host.test', ''],
            ['example.test/path', ''],
            ['user@example.test', ''],
            ['example.test..', ''],
            ['example.test?query=yes', ''],
            ["example.test\r\nX-Forged: y", ''],
        ];

        foreach ($cases as $case) {
            $this->withServerValues(['HTTP_HOST' => $case[0]], function () use ($case) {
                $cookie = new Cookie();
                $cookie->setCookieParameters();
                self::assertSame($case[1], $cookie->getCookieDefaultDomain(), $case[0]);
            });
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAutomaticallyDerivedSettingsFollowCurrentRequest()
    {
        $original = $_SERVER;
        try {
            $_SERVER['HTTP_HOST'] = 'first.example.test';
            $_SERVER['HTTPS'] = 'on';
            $cookie = new Cookie();
            $cookie->setCookieDefaultPath('/preserved');
            self::assertTrue($cookie->setCookie('first', 'a', 2000000000));
            self::assertSame('.first.example.test', $cookie->getCookieDefaultDomain());
            self::assertTrue($cookie->isCookieUseSecure());

            $_SERVER['HTTP_HOST'] = 'co.uk';
            $_SERVER['HTTPS'] = 'off';
            self::assertTrue($cookie->setCookie('second', 'b', 2000000000));
            self::assertSame('', $cookie->getCookieDefaultDomain());
            self::assertFalse($cookie->isCookieUseSecure());
            self::assertSame('/preserved', $cookie->getCookieDefaultPath());

            $_SERVER['HTTP_HOST'] = 'second.example.test';
            $_SERVER['HTTPS'] = 'on';
            self::assertTrue($cookie->setCookie('third', 'c', 2000000000));
            self::assertSame('.second.example.test', $cookie->getCookieDefaultDomain());
            self::assertTrue($cookie->isCookieUseSecure());
        } finally {
            $_SERVER = $original;
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testExplicitCookieSettingsStayFixedAcrossRequests()
    {
        $original = $_SERVER;
        try {
            $_SERVER['HTTP_HOST'] = 'first.example.test';
            $_SERVER['HTTPS'] = 'on';
            $cookie = new Cookie();
            $cookie->setCookieParameters('/explicit', 'pre_', 'allowed.example.test', false);
            self::assertTrue($cookie->setCookie('first', 'a', 2000000000));

            $_SERVER['HTTP_HOST'] = 'second.example.test';
            $_SERVER['HTTPS'] = 'off';
            self::assertTrue($cookie->setCookie('second', 'b', 2000000000));
            self::assertSame('/explicit', $cookie->getCookieDefaultPath());
            self::assertSame('pre_', $cookie->getCookieDefaultPrefix());
            self::assertSame('allowed.example.test', $cookie->getCookieDefaultDomain());
            self::assertFalse($cookie->isCookieUseSecure());
        } finally {
            $_SERVER = $original;
        }
    }

    public function testResettingExplicitDomainReenablesSafeAutomaticInference()
    {
        $this->withServerValues(['HTTP_HOST' => 'github.io'], function () {
            $cookie = new Cookie();
            $cookie->setCookieDefaultDomain('explicit.example.test');
            $cookie->setCookieParameters();
            self::assertSame('explicit.example.test', $cookie->getCookieDefaultDomain());

            $cookie->setCookieDefaultDomain(null);
            $cookie->setCookieParameters();
            self::assertSame('', $cookie->getCookieDefaultDomain());
        });
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
    public function testCookieSecureDefaultUsesOnlyExplicitlyTrustedProxy()
    {
        $this->withServerValues([
            'HTTPS' => 'off',
            'REMOTE_ADDR' => '192.0.2.15',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_HOST' => 'www.example.test:8443',
        ], function () {
            $cookie = new Cookie();
            $cookie->setCookieParameters('/account');
            self::assertFalse($cookie->isCookieUseSecure());

            $cookie->setCookieTrustedProxies(['192.0.2.0/24']);
            self::assertTrue($cookie->isCookieUseSecure());
            self::assertSame(['192.0.2.0/24'], $cookie->getCookieTrustedProxies());

            $cookie->setCookieTrustedProxies([]);
            self::assertFalse($cookie->isCookieUseSecure());
        });
    }

    public function testExplicitCookieSecureOverrideWinsOverTrustedProxy()
    {
        $this->withServerValues([
            'HTTPS' => 'off',
            'REMOTE_ADDR' => '192.0.2.15',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ], function () {
            $cookie = new Cookie();
            $cookie->setCookieParameters('/', null, 'example.test', false);
            $cookie->setCookieTrustedProxies(['192.0.2.0/24']);
            self::assertFalse($cookie->isCookieUseSecure());

            $cookie->setCookieUseSecure(null);
            $cookie->setCookieParameters('/', null, null, null);
            self::assertTrue($cookie->isCookieUseSecure());

            $cookie->setCookieUseSecure(false);
            $cookie->setCookieTrustedProxies([]);
            self::assertFalse($cookie->isCookieUseSecure());
        });
    }

    public function testInvalidTrustedProxyConfigurationIsRejected()
    {
        foreach (['192.0.2.1/33', '2001:db8::1/129', 'not-an-address', ''] as $proxy) {
            try {
                (new Cookie())->setCookieTrustedProxies([$proxy]);
                self::fail('Expected invalid trusted proxy to be rejected: ' . $proxy);
            } catch (\InvalidArgumentException $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function testLegacyFacadeCanConfigureTrustedProxies()
    {
        $this->withServerValues([
            'HTTPS' => 'off',
            'REMOTE_ADDR' => '2001:db8::15',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ], function () {
            $legacy = new \TorneLIB\MODULE_NETWORK();
            $legacy->setCookieTrustedProxies(['2001:db8::/32']);
            self::assertTrue($legacy->isCookieUseSecure());
            self::assertSame(['2001:db8::/32'], $legacy->getCookieTrustedProxies());
        });
    }

}
