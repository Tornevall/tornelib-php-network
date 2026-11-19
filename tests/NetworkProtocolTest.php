<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Module\Network;
use TorneLIB\Module\Network\Statics;

class NetworkProtocolTest extends TestCase
{
    public static function httpsServerCases()
    {
        return [
            ['on', true],
            ['1', true],
            ['true', true],
            ['off', false],
            ['0', false],
            ['', false],
            [null, false],
        ];
    }

    public function testProtocolAndSecureIndicatorStayConsistent()
    {
        $original = $_SERVER;
        try {
            foreach (self::httpsServerCases() as $case) {
                list($indicator, $secure) = $case;
                if ($indicator === null) {
                    unset($_SERVER['HTTPS']);
                } else {
                    $_SERVER['HTTPS'] = $indicator;
                }

                $expectedProtocol = $secure ? 'https' : 'http';
                self::assertSame($secure, Statics::getCurrentServerProtocol());
                self::assertSame($expectedProtocol, Statics::getCurrentServerProtocol(true));
                self::assertSame($secure, (new Network())->getIsHttps());
                self::assertSame($expectedProtocol, (new Network())->getProtocol());
            }
        } finally {
            $_SERVER = $original;
        }
    }

    public function testUntrustedForwardedProtoDoesNotImplicitlyChangeProtocol()
    {
        $original = $_SERVER;
        try {
            unset($_SERVER['HTTPS']);
            $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

            self::assertFalse(Statics::getCurrentServerProtocol());
            self::assertSame('http', Statics::getCurrentServerProtocol(true));
        } finally {
            $_SERVER = $original;
        }
    }
    public function testForwardedProtocolUsesOnlyTrustedDirectPeers()
    {
        $cases = [
            ['192.0.2.15', 'https', ['192.0.2.0/24'], true],
            ['192.0.2.15', 'HTTPS', ['192.0.2.15'], true],
            ['192.0.3.15', 'https', ['192.0.2.0/24'], false],
            ['192.0.2.15', 'https', [], false],
            ['192.0.2.15', 'http', ['192.0.2.0/24'], false],
            ['192.0.2.15', 'https,http', ['192.0.2.0/24'], false],
            ['2001:db8::15', 'https', ['2001:db8::/32'], true],
            ['2001:db8::15', 'https', ['2001:db8::15'], true],
            ['2001:db9::15', 'https', ['2001:db8::/32'], false],
            ['2001:db8::15', 'https', ['192.0.2.0/24'], false],
            ['192.0.2.15', 'https', ['2001:db8::/32'], false],
            ['not-an-ip', 'https', ['0.0.0.0/0'], false],
            ['192.0.2.15', 'https', ['invalid-cidr'], false],
        ];

        $original = $_SERVER;
        try {
            foreach ($cases as $case) {
                $_SERVER['HTTPS'] = 'off';
                $_SERVER['REMOTE_ADDR'] = $case[0];
                $_SERVER['HTTP_X_FORWARDED_PROTO'] = $case[1];
                $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.0.2.15';

                self::assertSame(
                    $case[3],
                    Statics::getCurrentServerProtocol(false, $case[2]),
                    $case[0] . '/' . $case[1]
                );
                self::assertSame(
                    $case[3] ? 'https' : 'http',
                    Statics::getCurrentServerProtocol(true, $case[2])
                );
                self::assertSame(
                    $case[3],
                    (new Network())->getIsHttps($case[2])
                );
                self::assertSame(
                    $case[3] ? 'https' : 'http',
                    (new Network())->getProtocol($case[2])
                );
                self::assertSame(
                    $case[3],
                    (new \TorneLIB\MODULE_NETWORK())->getIsSecureHttp(false, $case[2])
                );
            }
        } finally {
            $_SERVER = $original;
        }
    }

    public function testForgedForwardedForHeaderNeverEstablishesProxyTrust()
    {
        $original = $_SERVER;
        try {
            $_SERVER['HTTPS'] = 'off';
            $_SERVER['REMOTE_ADDR'] = '198.51.100.9';
            $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.0.2.8';
            $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

            self::assertFalse(
                Statics::getCurrentServerProtocol(false, ['192.0.2.0/24'])
            );

            $_SERVER['HTTPS'] = 'on';
            self::assertTrue(
                Statics::getCurrentServerProtocol(false, ['192.0.2.0/24'])
            );
        } finally {
            $_SERVER = $original;
        }
    }

    public function testNonStringForwardedSchemeIsNeverAccepted()
    {
        $original = $_SERVER;
        try {
            $_SERVER['HTTPS'] = 'off';
            $_SERVER['REMOTE_ADDR'] = '192.0.2.10';
            $_SERVER['HTTP_X_FORWARDED_PROTO'] = ['https'];

            self::assertFalse(
                Statics::getCurrentServerProtocol(false, ['192.0.2.0/24'])
            );
        } finally {
            $_SERVER = $original;
        }
    }

}
