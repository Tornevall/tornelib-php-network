<?php

namespace TorneLIB\Module;

use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Helpers\NetUtils;
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

    /**
     * getGitTagsByUrl
     *
     * From 6.1, the $keepCredentials has no effect.
     *
     * @param $url
     * @param bool $numericsOnly
     * @param bool $numericsSanitized
     * @return array
     * @throws ExceptionHandler
     * @since 6.0.4
     * @deprecated Method moved to netcurl-6.1, use that directly instead of this old reference pointer.
     */
    public function getGitTagsByUrl($url, $numericsOnly = false, $numericsSanitized = false)
    {
        if (!class_exists('TorneLIB\Helpers\NetUtils')) {
            throw new ExceptionHandler(
                sprintf(
                    'Can not use %s since the function is missing in %s.',
                    __FUNCTION__,
                    __CLASS__
                ),
                Constants::LIB_METHOD_OR_LIBRARY_UNAVAILABLE
            );
        }
        return call_user_func_array(
            [
                (new NetUtils()),
                __FUNCTION__,
            ],
            [$url, $numericsOnly, $numericsSanitized]
        );
    }


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
