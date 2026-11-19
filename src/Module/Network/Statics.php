<?php

namespace TorneLIB\Module\Network;

use TorneLIB\Module\Network;

/**
 * Class Statics Static requests without dependencies.
 *
 * @package TorneLIB\Module\Network
 * @version 6.1.5
 */
abstract class Statics
{
    /**
     * Report whether the request uses HTTPS. A forwarded scheme is accepted
     * only when REMOTE_ADDR matches an explicitly configured trusted proxy.
     * Reverse proxies must strip or replace client-supplied forwarding headers.
     *
     * @param bool $returnProtocol
     * @param array $trustedProxies IP addresses or CIDR ranges
     * @return bool|string
     * @since 6.0.15
     */
    public static function getCurrentServerProtocol(
        bool $returnProtocol = false,
        array $trustedProxies = []
    ) {
        $https = isset($_SERVER['HTTPS']) && is_scalar($_SERVER['HTTPS']) ?
            strtolower(trim((string)$_SERVER['HTTPS'])) : '';
        $isSecure = in_array($https, ['on', '1', 'true'], true);

        if (!$isSecure && !empty($trustedProxies) &&
            isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR']) &&
            self::isTrustedProxy($_SERVER['REMOTE_ADDR'], $trustedProxies)
        ) {
            $forwarded = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
                is_string($_SERVER['HTTP_X_FORWARDED_PROTO']) ?
                strtolower(trim($_SERVER['HTTP_X_FORWARDED_PROTO'])) : '';

            // Reject ambiguous multi-proxy headers and unexpected values.
            $isSecure = $forwarded === 'https';
        }

        return $returnProtocol ? ($isSecure ? 'https' : 'http') : $isSecure;
    }

    /**
     * Check the direct peer, never X-Forwarded-For, against an allowlist.
     *
     * @param string $remoteAddress
     * @param array $trustedProxies
     * @return bool
     */
    private static function isTrustedProxy(string $remoteAddress, array $trustedProxies): bool
    {
        if (filter_var($remoteAddress, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $address = new Address();
        foreach ($trustedProxies as $allowed) {
            if (!is_string($allowed)) {
                continue;
            }

            if (filter_var($allowed, FILTER_VALIDATE_IP) !== false) {
                if (inet_pton($allowed) === inet_pton($remoteAddress)) {
                    return true;
                }
                continue;
            }

            try {
                if ($address->isIpInRange($remoteAddress, $allowed)) {
                    return true;
                }
            } catch (\Exception $exception) {
                // Invalid ranges grant no trust.
                continue;
            }
        }

        return false;
    }

    /**
     * @param string $redirectUrl
     * @param bool $replaceHeader
     * @param int $responseCode
     * @since 6.1.0
     */
    public static function redirect(string $redirectUrl, bool $replaceHeader = false, int $responseCode = 301)
    {
        (new Domain())->redirect($redirectUrl, $replaceHeader, $responseCode);
    }

    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array(
            [
                new Network(),
                $name,
            ],
            $arguments
        );
    }
}
