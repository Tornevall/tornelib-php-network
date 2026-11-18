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

    /**
     * @dataProvider httpsServerCases
     */
    public function testProtocolAndSecureIndicatorStayConsistent($indicator, $secure)
    {
        $original = $_SERVER;
        try {
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
}
