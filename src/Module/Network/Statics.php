<?php

namespace TorneLIB\Module\Network;

abstract class Statics
{
    /**
     * @param bool $returnProtocol
     *
     * @return bool|string
     * @since 6.0.15
     */
    public static function getCurrentServerProtocol($returnProtocol = false)
    {
        if (isset($_SERVER['HTTPS'])) {
            if ($_SERVER['HTTPS'] == "on") {
                if (!$returnProtocol) {
                    return true;
                } else {
                    return "https";
                }
            } else {
                if (!$returnProtocol) {
                    return false;
                } else {
                    return "http";
                }
            }
        }
        if (!$returnProtocol) {
            return false;
        } else {
            return "http";
        }
    }
}