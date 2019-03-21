<?php

/**
 * Copyright 2019 Tomas Tornevall & Tornevall Networks
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Tornevall Networks netCurl library - Yet another http- and network communicator library
 * Each class in this library has its own version numbering to keep track of where the changes are. However, there is a
 * major version too.
 *
 * @package TorneLIB
 * @version 6.1.0
 */

namespace TorneLIB\Module;

use TorneLIB\Deprecated\DeprecateNet;

if (!defined('NETCURL_NETWORK_RELEASE')) {
    define('NETCURL_NETWORK_RELEASE', '6.1.0');
}
if (!defined('NETCURL_NETWORK_MODIFY')) {
    define('NETCURL_NETWORK_MODIFY', '2019-01-30');
}

class Network
{
    /**
     * @var DeprecateNet
     * @since 6.1.0
     */
    private $Deprecated;

    /**
     * @var array $proxyHeaders List of scannable proxy headers from webserver.
     *
     * @since 6.0
     */
    private $proxyHeaders = array(
        'HTTP_VIA',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED',
        'HTTP_CLIENT_IP',
        'HTTP_FORWARDED_FOR_IP',
        'VIA',
        'X_FORWARDED_FOR',
        'FORWARDED_FOR',
        'X_FORWARDED',
        'FORWARDED',
        'CLIENT_IP',
        'FORWARDED_FOR_IP',
        'HTTP_PROXY_CONNECTION'
    );
    /**
     * @var array $proxyAddressList Address list with catched proxy addresses if published by client.
     *
     * @since 6.1.0
     */
    private $proxyAddressList = array();

    /**
     * MODULE_NETWORK constructor.
     * @since 6.1.0
     */
    public function __construct()
    {
        $this->fetchProxyHeaders();
    }

    /**
     * Pick up data from browser client if any.
     */
    private function fetchProxyHeaders()
    {
        if (isset($_SERVER) && isset($_SERVER['REMOTE_ADDR'])) {
            foreach ($this->proxyHeaders as $headerKey) {
                $this->proxyAddressList[$headerKey] = isset($_SERVER[$headerKey]) ? $_SERVER[$headerKey] : null;
            }
        }

        return $this->proxyAddressList;
    }

    /**
     * Return information about proxy client ip addresses if any.
     *
     * @param bool $withValues When false, return all data regardless of if the headers are empty.
     * @return array
     * @since 6.1.0
     */
    public function getProxyData($withValues = true)
    {
        $return = array();
        foreach ($this->proxyAddressList as $key => $value) {
            if (($withValues && !empty($value)) || !$withValues) {
                $return[$key] = $value;
            }
        }

        return $return;
    }


    /**
     * @param $name
     * @since 6.1.0
     */
    public function __get($name)
    {

    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     * @since 6.1.0
     */
    public function __call($name, $arguments)
    {
        $return = null;

        if (method_exists($this->Deprecated, $name)) {
            return call_user_func_array(array($this->Deprecated, $name), $arguments);
        }

        if (substr($name, 0, 3) === 'get') {
            $what = lcfirst(substr($name, 3));
            if (isset($this->{$what})) {
                $return = $this->{$what};
                if (is_null($return)) {
                    // Return directly if null here.
                    return $return;
                }
            }
        }

        if (is_null($return)) {
            throw new \Exception(sprintf(
                'Method "%s" for %s does not exist or has been deprecated',
                $name,
                __CLASS__
            ), 1);
        }

        return $return;
    }

}