<?php

namespace TorneLIB\Module\Network;

use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Module\Exception\AddressException;

/**
 * IPv4/CIDR and IP reverse-address helpers.
 *
 * @version 6.1.5
 */
class Address
{
    /**
     * Avoid allocating unbounded PHP arrays for broad networks.
     */
    const MAX_CIDR_RANGE_SIZE = 65536;

    /**
     * Validate a CIDR with an IPv4 base address and a prefix from 0 to 32.
     *
     * @param string $cidr
     * @return array
     * @throws AddressException
     */
    private function parseIpv4Cidr(string $cidr): array
    {
        if (!preg_match('/^([^\/]+)\/([0-9]{1,2})$/', $cidr, $matches) ||
            filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false ||
            (int)$matches[2] > 32
        ) {
            throw new AddressException(
                'Not a proper IPv4 CIDR range.',
                Constants::LIB_NETWORK_BAD_CIDR_STRING
            );
        }

        return [ip2long($matches[1]), (int)$matches[2]];
    }

    /**
     * Return all IPv4 addresses in a CIDR, including its first and last address.
     * CIDRs containing more than 65536 addresses must not be enumerated.
     *
     * @param string $mask
     * @return array
     * @throws AddressException
     */
    public function getRangeFromMask(string $mask): array
    {
        list($baseAddress, $prefix) = $this->parseIpv4Cidr($mask);
        $count = pow(2, 32 - $prefix);

        if ($count > self::MAX_CIDR_RANGE_SIZE) {
            throw new AddressException(
                'CIDR range is too large to enumerate safely.',
                Constants::LIB_NETWORK_BAD_CIDR_STRING
            );
        }

        $networkMask = $prefix === 0 ? 0 : (-1 << (32 - $prefix));
        $networkAddress = $baseAddress & $networkMask;
        $addresses = [];

        for ($index = 0; $index < $count; $index++) {
            $addresses[] = long2ip($networkAddress | $index);
        }

        return $addresses;
    }

    /**
     * Test IPv4 membership in a CIDR. IPv6 is not supported by this method.
     *
     * @param string $IP
     * @param string $CIDR
     * @return bool
     * @throws AddressException
     */
    public function isIpInRange(string $IP, string $CIDR): bool
    {
        list($baseAddress, $prefix) = $this->parseIpv4Cidr($CIDR);
        if (filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            throw new AddressException(
                'Not a valid IPv4 address.',
                Constants::LIB_NETWORK_INVALID_IPV4
            );
        }

        $address = ip2long($IP);
        $networkMask = $prefix === 0 ? 0 : (-1 << (32 - $prefix));

        return ($address & $networkMask) === ($baseAddress & $networkMask);
    }

    /**
     * Translate a valid IPv6 address to reverse nibbles.
     *
     * @param string $ipAddress
     * @return string
     */
    public function getArpaFromIpv6(string $ipAddress): string
    {
        if (!$this->isIpv6($ipAddress)) {
            return '';
        }

        $binary = inet_pton($ipAddress);
        if ($binary === false) {
            return '';
        }

        $hex = unpack('H*hex', $binary);

        return implode('.', array_reverse(str_split($hex['hex'])));
    }

    /**
     * Translate a valid IPv4 address to reverse octets.
     *
     * @param string $ipAddress
     * @return string
     * @throws ExceptionHandler
     */
    public function getArpaFromIpv4(string $ipAddress): string
    {
        if (!$this->isIpv4($ipAddress)) {
            throw new ExceptionHandler(
                'Not a valid IPv4 address.',
                Constants::LIB_NETWORK_INVALID_IPV4
            );
        }

        return implode('.', array_reverse(explode('.', $ipAddress)));
    }

    /**
     * Translate a full 32-nibble IPv6 PTR name (or naked reverse nibbles).
     *
     * @param string $arpaOctets
     * @return string
     * @throws AddressException
     */
    public function getIpv6FromOctets(
        string $arpaOctets = '0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0'
    ): string {
        $reverse = preg_replace('/\.(?:ip6\.arpa|ip\.int)\.?$/i', '', $arpaOctets);
        $nibbles = explode('.', $reverse);

        if (count($nibbles) !== 32) {
            throw new AddressException('IPv6 ARPA must contain exactly 32 hex nibbles.');
        }

        foreach ($nibbles as $nibble) {
            if (!preg_match('/^[a-f0-9]$/i', $nibble)) {
                throw new AddressException('Invalid IPv6 ARPA nibble.');
            }
        }

        $hex = implode('', array_reverse($nibbles));
        $binary = pack('H*', $hex);
        $address = inet_ntop($binary);
        if ($address === false) {
            throw new AddressException('Cannot decode IPv6 ARPA octets.');
        }

        return $address;
    }

    /**
     * @param string $ipAddress
     * @return bool
     */
    private function isIpv6(string $ipAddress): bool
    {
        return filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * @param string $ipAddress
     * @return bool
     */
    private function isIpv4(string $ipAddress): bool
    {
        return filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * @param string $ipAddress
     * @return bool
     */
    public function isValidIpAddress(string $ipAddress): bool
    {
        return $this->isIpv4($ipAddress) || $this->isIpv6($ipAddress);
    }

    /**
     * @param string $ipAddress
     * @param bool $returnIpType
     * @return int|string
     * @throws ExceptionHandler
     */
    public function getArpaFromAddr(string $ipAddress, $returnIpType = false)
    {
        if ($this->isIpv6($ipAddress)) {
            return $returnIpType ? 6 : $this->getArpaFromIpv6($ipAddress);
        }

        if ($this->isIpv4($ipAddress)) {
            return $returnIpType ? 4 : $this->getArpaFromIpv4($ipAddress);
        }

        throw new ExceptionHandler(
            sprintf('Invalid ip address "%s" in request %s.', $ipAddress, __FUNCTION__),
            Constants::LIB_NETCURL_INVALID_IP_ADDRESS
        );
    }

    /**
     * @param string $ipAddress
     * @return string
     * @throws ExceptionHandler
     */
    public function getArpa($ipAddress): string
    {
        return $this->getArpaFromAddr($ipAddress);
    }

    /**
     * @param string $ipAddress
     * @return int
     * @throws ExceptionHandler
     */
    public function getIpType($ipAddress): int
    {
        return $this->getArpaFromAddr($ipAddress, true);
    }
}
