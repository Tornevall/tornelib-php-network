<?php

namespace TorneLIB\Module;

use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Module\Network\Statics;
use TorneLIB\IO\Data\Strings;
use TorneLIB\Module\Bit;

/**
 * Class DeprecateNet
 *
 * Network Module Deprecation Class.
 *
 * @package TorneLIB\Module
 */
class DeprecateNet
{
    /**
     * @var bool $isDeprecated If this is true while you are running something, you'll now you're soon out of time.
     * @since 6.1.0
     */
    public $isDeprecated = true;

    /**
     * @var $BIT Bit
     */
    public $BIT;

    public function __construct()
    {
        $this->BIT = new Bit();
    }

    public function __call($name, $arguments)
    {
        $return = null;

        $camelCaseMethodName = Strings::returnCamelCase($name);

        if (method_exists($this, $camelCaseMethodName)) {
            $return = call_user_func_array(array($this, $camelCaseMethodName), $arguments);
        }

        return $return;
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
    private function base64urlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * base64_decode
     * @param $data
     * @return string
     */
    private function base64urlDecode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
