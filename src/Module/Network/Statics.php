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
     * Return information about currently used server protocol (HTTP or HTTPS).
     * If returnProtocol is false, returned result will be true for https.
     *
     * @param bool $returnProtocol
     * @return bool|string
     * @since 6.0.15
     */
    public static function getCurrentServerProtocol(bool $returnProtocol = false)
    {
        if (isset($_SERVER['HTTPS'])) {
            if ($_SERVER['HTTPS'] == "on") {
                if (!$returnProtocol) {
                    $return = true;
                } else {
                    $return = "https";
                }
            } else {
                if (!$returnProtocol) {
                    $return = false;
                } else {
                    $return = "http";
                }
            }

            return $return;
        }

        if (!$returnProtocol) {
            $return = false;
        } else {
            $return = "http";
        }

        return $return;
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
