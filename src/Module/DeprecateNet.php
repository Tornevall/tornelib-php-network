<?php

namespace TorneLIB\Module;

class DeprecateNet
{
    public $isDeprecated = true;

    public function __get($name)
    {

    }

    /**
     * base64_encode
     *
     * @param $data
     *
     * @return string
     */
    public function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * base64_decode
     *
     * @param $data
     *
     * @return string
     */
    public function base64url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}