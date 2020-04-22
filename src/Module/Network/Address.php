<?php

namespace TorneLIB\Module\Network;

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
     * @param null $mask
     * @return array
     * @since 5.0.0
     */
    public function getRangeFromMask($mask = null)
    {
        $addresses = [];
        @list($ip, $len) = explode('/', $mask);
        if (($min = ip2long($ip)) !== false) {
            $max = ($min | (1 << (32 - $len)) - 1);
            for ($i = $min; $i < $max; $i++) {
                $addresses[] = long2ip($i);
            }
        }

        return $addresses;
    }

    /**
     * Test if the given ip address is in the netmask range (not ipv6 compatible yet)
     *
     * @param $IP
     * @param $CIDR
     * @return bool
     * @since 5.0.0
     */
    public function isIpInRange($IP, $CIDR)
    {
        [$net, $mask] = explode("/", $CIDR);
        $ip_net = ip2long($net);
        $ip_mask = ~((1 << (32 - $mask)) - 1);
        $ip_ip = ip2long($IP);
        $ip_ip_net = $ip_ip & $ip_mask;

        return ($ip_ip_net == $ip_net);
    }

    /**
     * Translate ipv6 address to reverse octets
     *
     * @param string $ipAddr
     * @return string
     * @since 5.0.0
     */
    public function getArpaFromIpv6($ipAddr = '::')
    {
        if (filter_var($ipAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            return null;
        }
        $unpackedAddr = @unpack('H*hex', inet_pton($ipAddr));
        $hex = $unpackedAddr['hex'];

        return implode('.', array_reverse(str_split($hex)));
    }

    /**
     * Translate ipv4 address to reverse octets
     *
     * @param string $ipAddr
     * @return string
     * @since 5.0.0
     */
    public function getArpaFromIpv4($ipAddr = '127.0.0.1')
    {
        if (filter_var($ipAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return implode(".", array_reverse(explode(".", $ipAddr)));
        }

        return null;
    }

    /**
     * Translate ipv6 reverse octets to ipv6 address
     *
     * @param string $arpaOctets
     * @return string
     * @since 5.0.0
     */
    public function getIpv6FromOctets(
        $arpaOctets = '0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0'
    ) {
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
}
