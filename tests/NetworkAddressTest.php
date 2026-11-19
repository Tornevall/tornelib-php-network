<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Module\Network\Address;

class NetworkAddressTest extends TestCase
{
    public static function validAddressCases()
    {
        return [
            ['127.0.0.1', 4, '1.0.0.127'],
            ['192.0.2.7', 4, '7.2.0.192'],
            ['::1', 6, '1.' . str_repeat('0.', 30) . '0'],
        ];
    }

    /**
     * @dataProvider validAddressCases
     */
    public function testAddressTypeAndReverseArpa($address, $type, $arpa)
    {
        $network = new Address();

        self::assertTrue($network->isValidIpAddress($address));
        self::assertSame($type, $network->getIpType($address));
        self::assertSame($arpa, $network->getArpa($address));
    }

    public function testInvalidAddressIsRejected()
    {
        $network = new Address();
        self::assertFalse($network->isValidIpAddress('not-an-address'));
        self::assertFalse($network->isValidIpAddress('256.0.0.1'));
        self::assertFalse($network->isValidIpAddress(''));

        $this->expectException(ExceptionHandler::class);
        $network->getIpType('256.0.0.1');
    }

    public function testIPv6ArpaRoundTripWithAndWithoutSuffix()
    {
        $network = new Address();
        $ip = '2001:db8::1';
        $reverse = $network->getArpaFromIpv6($ip);

        self::assertSame('2001:db8::1', $network->getIpv6FromOctets($reverse));
        self::assertSame('2001:db8::1', $network->getIpv6FromOctets($reverse . '.ip6.arpa'));
    }

    public function testInvalidIPv4ReverseLookupThrows()
    {
        $this->expectException(ExceptionHandler::class);
        (new Address())->getArpaFromIpv4('999.10.10.10');
    }

    public function testInvalidIPv6ReverseLookupReturnsEmptyString()
    {
        self::assertSame('', (new Address())->getArpaFromIpv6('invalid-v6'));
    }

    public static function cidrRangeCases()
    {
        return [
            ['192.0.2.0/30', ['192.0.2.0', '192.0.2.1', '192.0.2.2', '192.0.2.3']],
            ['192.0.2.0/31', ['192.0.2.0', '192.0.2.1']],
            ['192.0.2.42/32', ['192.0.2.42']],
            ['192.0.2.1/30', ['192.0.2.0', '192.0.2.1', '192.0.2.2', '192.0.2.3']],
            ['255.255.255.254/31', ['255.255.255.254', '255.255.255.255']],
        ];
    }

    /**
     * @dataProvider cidrRangeCases
     */
    public function testCompleteCidrRangeIncludesEveryAddress($cidr, $expected)
    {
        self::assertSame($expected, (new Address())->getRangeFromMask($cidr));
    }

    public function testOversizedCidrEnumerationIsRejected()
    {
        $this->expectException(ExceptionHandler::class);
        (new Address())->getRangeFromMask('0.0.0.0/0');
    }

    public static function invalidCidrCases()
    {
        return [
            ['192.0.2.0'],
            ['bad-address/24'],
            ['192.0.2.0/33'],
            ['192.0.2.0/-1'],
            ['192.0.2.0/not-a-prefix'],
            ['192.0.2.0/24/trailing'],
        ];
    }

    /**
     * @dataProvider invalidCidrCases
     */
    public function testInvalidCidrRangeIsRejected($cidr)
    {
        $this->expectException(ExceptionHandler::class);
        (new Address())->getRangeFromMask($cidr);
    }

    public static function membershipCases()
    {
        return [
            ['192.0.2.10', '192.0.2.0/24', true],
            ['192.0.3.10', '192.0.2.0/24', false],
            ['192.0.2.10', '192.0.2.10/24', true],
            ['192.0.2.42', '192.0.2.42/32', true],
            ['192.0.2.43', '192.0.2.42/32', false],
            ['198.51.100.9', '0.0.0.0/0', true],
            ['192.0.2.1', '192.0.2.0/31', true],
        ];
    }

    /**
     * @dataProvider membershipCases
     */
    public function testCidrMembership($address, $cidr, $expected)
    {
        self::assertSame($expected, (new Address())->isIpInRange($address, $cidr));
    }

    public static function ipv6MembershipCases()
    {
        return [
            ['2001:db8::1', '2001:db8::/32', true],
            ['2001:db9::1', '2001:db8::/32', false],
            ['2001:db8:abcd:12::1', '2001:db8:abcd::/48', true],
            ['2001:db8:abce::1', '2001:db8:abcd::/48', false],
            ['2001:db8::42', '2001:db8::42/128', true],
            ['2001:db8::43', '2001:db8::42/128', false],
            ['::1', '::/0', true],
            ['::1', '::/128', false],
            ['2001:db8:7fff::1', '2001:db8::/33', true],
            ['2001:db8:8000::1', '2001:db8::/33', false],
            ['2001:db8::1', '2001:db8::12/64', true],
            ['2001:DB8::1', '2001:db8::/32', true],
            ['192.0.2.10', '2001:db8::/32', false],
            ['2001:db8::10', '192.0.2.0/24', false],
            ['::ffff:192.0.2.10', '192.0.2.0/24', false],
        ];
    }

    /**
     * @dataProvider ipv6MembershipCases
     */
    public function testIPv6CidrMembershipAndMixedAddressFamilies($address, $cidr, $expected)
    {
        self::assertSame($expected, (new Address())->isIpInRange($address, $cidr));
    }

    public static function invalidIpv6CidrCases()
    {
        return [
            ['2001:db8::1', '2001:db8::/129'],
            ['2001:db8::1', '2001:db8::/-1'],
            ['2001:db8::1', '2001:db8::/foo'],
            ['2001:db8::1', '2001:db8::/'],
            ['2001:db8::1', '2001:db8::'],
            ['2001:db8::1', '2001:db8::/128/extra'],
            ['2001:db8::1', 'bad-host/64'],
            ['2001:db8::1', '192.0.2.0/64'],
        ];
    }

    /**
     * @dataProvider invalidIpv6CidrCases
     */
    public function testInvalidIPv6CidrIsRejected($address, $cidr)
    {
        $this->expectException(ExceptionHandler::class);
        (new Address())->isIpInRange($address, $cidr);
    }

    public function testInvalidIPv6ClientAddressIsRejected()
    {
        $this->expectException(ExceptionHandler::class);
        (new Address())->isIpInRange('fe80::1%eth0', 'fe80::/10');
    }

    public function testIPv6MembershipIsAvailableThroughCompatibilityFacades()
    {
        self::assertTrue((new \TorneLIB\Module\Network())->isIpInRange('2001:db8::1234', '2001:db8::/32'));
        self::assertTrue((new \TorneLIB\MODULE_NETWORK())->isIpInRange('2001:db8::1234', '2001:db8::/32'));
        self::assertFalse(\TorneLIB\Module\Network\Statics::isIpInRange('2001:db9::1', '2001:db8::/32'));
    }

    public function testInvalidMembershipIpIsRejected()
    {
        $this->expectException(ExceptionHandler::class);
        (new Address())->isIpInRange('not-an-ip', '0.0.0.0/0');
    }

    public function testInvalidMembershipMaskIsRejected()
    {
        $this->expectException(ExceptionHandler::class);
        (new Address())->isIpInRange('192.0.2.1', '192.0.2.0/99');
    }
}
