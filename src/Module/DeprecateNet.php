<?php

namespace TorneLIB\Module;

use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Helpers\NetUtils;
use TorneLIB\IO\Data\Strings;
use TorneLIB\Module\Network\Statics;

/**
 * Class DeprecateNet
 * Network Module Deprecation Class.
 * @package TorneLIB\Module
 * @version 6.1.5
 */
class DeprecateNet
{
    /**
     * @var bool $isDeprecated If this is true while you are running something, you'll now you're soon out of time.
     * @since 6.1.0
     */
    public $isDeprecated = true;

    /**
     * @return NetUtils
     * @throws ExceptionHandler
     * @since 6.1.2
     */
    private function getNetUtils(): NetUtils
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
        return new NetUtils();
    }

    /**
     * getGitTagsByUrl
     *
     * From 6.1, the $keepCredentials has no effect.
     *
     * @param string $url
     * @param bool $numericsOnly
     * @param bool $numericsSanitized
     * @return array
     * @throws ExceptionHandler
     * @since 6.0.4
     * @deprecated Method moved to netcurl-6.1, use that directly instead of this old reference pointer.
     */
    public function getGitTagsByUrl(string $url, bool $numericsOnly = false, bool $numericsSanitized = false): array
    {
        return call_user_func_array(
            [
                ($this->getNetUtils()),
                __FUNCTION__,
            ],
            [$url, $numericsOnly, $numericsSanitized]
        );
    }

    /**
     * @return mixed
     * @throws ExceptionHandler
     * @since 6.1.2
     */
    public function getVersionTooOld(string $myVersion = '', string $gitUrl = '')
    {
        return call_user_func_array(
            [
                ($this->getNetUtils()),
                'getHigherVersions',
            ],
            [$gitUrl, $myVersion]
        );
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed|null
     * @since 6.1.0
     */
    public function __call($name, $arguments)
    {
        $return = null;

        $camelCaseMethodName = Strings::returnCamelCase($name);

        if (method_exists($this, $camelCaseMethodName)) {
            $return = call_user_func_array([$this, $camelCaseMethodName], $arguments);
        }

        return $return;
    }

    /**
     * @param bool $returnProtocol
     * @return bool|string
     * @since 6.1.0
     */
    public function isSecureHttp(bool $returnProtocol = false)
    {
        return Statics::getCurrentServerProtocol($returnProtocol);
    }


    /**
     * base64_encode.
     *
     * @param string $data
     * @return string
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * base64_decode.
     *
     * @param string $data
     * @return string
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function base64urlDecode(string $data): string
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
