<?php

namespace TorneLIB\Module\Network;

use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;

/**
 * Class Address IP- and arpas.
 *
 * @package TorneLIB\Module\Network
 * @version 6.1.0
 */
class Address
{
    /**
     * Get IP range from netmask.
     *
     * @param string $mask
     * @return array
     * @throws ExceptionHandler
     * @since 5.0.0
     */
    public function getRangeFromMask(string $mask): array
    {
        $addresses = [];
        $cidrLength = 0;
        $ip = '';
        if (!preg_match('/\//', $mask)) {
            throw new ExceptionHandler(
                'Not a proper CIDR range.', Constants::LIB_NETWORK_BAD_CIDR_STRING
            );
        }
        // Preferred method for higher PHP's shown below. We used list() before.
        //[$ip, $len] = explode('/', $mask);
        $explodeCidr = explode('/', $mask);
        if (isset($explodeCidr[1])) {
            $ip = $explodeCidr[0];
            $cidrLength = $explodeCidr[1];
        }
        $min = ip2long($ip);
        if ($min !== false) {
            $max = ($min | (1 << (32 - $cidrLength)) - 1);
            for ($i = $min; $i < $max; $i++) {
                $addresses[] = long2ip($i);
            }
        }

        return $addresses;
    }

    /**
     * Test if the given ip address is in the netmask range (not ipv6 compatible yet)
     *
     * @param string $IP
     * @param string $CIDR
     * @return bool
     * @throws ExceptionHandler
     * @since 5.0.0
     */
    public function isIpInRange(string $IP, string $CIDR): bool
    {
        if (!preg_match('/\//', $mask)) {
            throw new ExceptionHandler(
                'Not a proper CIDR range.', Constants::LIB_NETWORK_BAD_CIDR_STRING
            );
        }

        $explodeCidr = explode('/', $CIDR);
        if (isset($explodeCidr[1])) {
            $net = $explodeCidr[0];
            $mask = $explodeCidr[1];
        }
        $ip_net = ip2long($net);
        $ip_mask = ~((1 << (32 - $mask)) - 1);
        $ip_ip = ip2long($IP);
        $ip_ip_net = $ip_ip & $ip_mask;

        return ($ip_ip_net == $ip_net);
    }

    /**
     * Translate ipv6 address to reverse octets
     *
     * @param string $ipAddress
     * @return string
     * @since 5.0.0
     */
    public function getArpaFromIpv6(string $ipAddress): string
    {
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            return '';
        }
        $unpackedAddress = @unpack('H*hex', inet_pton($ipAddress));
        $hex = $unpackedAddress['hex'];

        return implode('.', array_reverse(str_split($hex)));
    }

    /**
     * Translate ipv4 address to reverse octets
     *
     * @param string $ipAddress
     * @return string
     * @throws ExceptionHandler
     * @since 5.0.0
     */
    public function getArpaFromIpv4(string $ipAddress): string
    {
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $return = implode(".", array_reverse(explode(".", $ipAddress)));
        } else {
            throw new ExceptionHandler(
                'Not a valid IPv4 address.',
                Constants::LIB_NETWORK_INVALID_IPV4
            );
        }

        return $return;
    }

    /**
     * Translate ipv6 reverse octets to ipv6 address
     *
     * @param string $arpaOctets
     * @return string
     * @since 5.0.0
     */
    public function getIpv6FromOctets(
        string $arpaOctets = '0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0'
    ): string {
        return @inet_ntop(
            pack(
                'H*',
                implode(
                    "",
                    array_reverse(
                        explode(
                            ".",
                            preg_replace(
                                "/\.ip6\.arpa$|\.ip\.int$/",
                                '',
                                $arpaOctets
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * @param string $ipAddress
     * @return bool
     * @since 6.1.0
     */
    private function isIpv6(string $ipAddress): bool
    {
        return filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    /**
     * @param string $ipAddress
     * @return bool
     * @since 6.1.0
     */
    private function isIpv4(string $ipAddress): bool
    {
        return filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    /**
     * @param string $ipAddress
     * @return bool
     * @since 6.1.5
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
     * @since 5.0.0
     */
    public function getArpaFromAddr(string $ipAddress, $returnIpType = false)
    {
        if ($this->isIpv6($ipAddress)) {
            if ($returnIpType) {
                $return = 6;
            } else {
                $return = $this->getArpaFromIpv6($ipAddress);
            }
        } elseif ($this->isIpv4($ipAddress)) {
            if ($returnIpType) {
                $return = 4;
            } else {
                $return = $this->getArpaFromIpv4($ipAddress);
            }
        } else {
            throw new ExceptionHandler(
                sprintf(
                    'Invalid ip address "%s" in request %s.',
                    $ipAddress,
                    __FUNCTION__
                ),
                Constants::LIB_NETCURL_INVALID_IP_ADDRESS
            );
        }

        return $return;
    }

    /**
     * @param $ipAddress
     * @return string
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function getArpa($ipAddress): string
    {
        return $this->getArpaFromAddr($ipAddress);
    }

    /**
     * Get type of ip address. Returns 0 if no type. IP Protocols from netcurl is deprecated.
     *
     * @param $ipAddress
     * @return int
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function getIpType($ipAddress): int
    {
        return $this->getArpaFromAddr($ipAddress, true);
    }
}
