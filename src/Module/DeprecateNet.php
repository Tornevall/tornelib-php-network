<?php

namespace TorneLIB\Module;

use TorneLIB\Module\Network\Statics;

class DeprecateNet
{
    public $isDeprecated = true;

    public function __get($name)
    {
        switch ($name) {
            default:
                throw new \Exception(sprintf('Method "%s" does not exist in the deprecated library.', $name), 404);
        }
    }

    /**
     * @param bool $returnProtocol
     * @return bool|string
     * @since 6.1.0
     */
    public function isSecureHttp($returnProtocol = false)
    {
        return Statics::getCurrentServerProtocol($returnProtocol);
    }


    /**
     * base64_encode
     * @param $data
     * @return string
     */
    private function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * base64_decode
     * @param $data
     * @return string
     */
    private function base64url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
