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

use TorneLIB\Module\Network\Address;
use TorneLIB\Module\Network\Statics;

if (!defined('NETCURL_NETWORK_RELEASE')) {
    define('NETCURL_NETWORK_RELEASE', '6.1.0');
}
if (!defined('NETCURL_NETWORK_MODIFY')) {
    define('NETCURL_NETWORK_MODIFY', '2019-01-30');
}

class Network
{
    /**
     * @var $DEPRECATED DeprecateNet
     * @since 6.1.0
     */
    private $DEPRECATED;

    private $classMap = array(
        'TorneLIB\Module\Network\Address'
    );

    /**
     * @var $ADDRESS Address
     */
    public $ADDRESS;

    /**
     * MODULE_NETWORK constructor.
     * @since 6.1.0
     */
    public function __construct()
    {
        $this->DEPRECATED = new DeprecateNet();
        $this->ADDRESS = new Address();
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
        return $this->ADDRESS->getProxyData($withValues);
    }

    /**
     * @return mixed
     * @since 6.1.0
     */
    public function getIsSecureHttp($returnProtocol = false)
    {
        return Statics::getCurrentServerProtocol($returnProtocol);
    }

    /**
     * Make sure we always return a "valid" http-host from HTTP_HOST. If the variable is missing, this will fall back to localhost.
     *
     * @return string
     * @sice 6.0.15
     */
    public function getHttpHost()
    {
        $httpHost = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "");
        if (empty($httpHost)) {
            $httpHost = "localhost";
        }

        return $httpHost;
    }

    /**
     * @return bool|string
     * @since 6.1.0
     */
    public function getProtocol()
    {
        return Statics::getCurrentServerProtocol(true);
    }

    /*** Functions below has a key role in deprecation and compatibility ***/

    /**
     * @param $name
     * @param $arguments
     * @return void|null
     * @throws \Exception
     * @since 6.1.0
     */
    private function get($name)
    {
        $return = null;

        $what = lcfirst(substr($name, 3));
        if (isset($this->{$what})) {
            return $this->{$what};
        }

        throw new \Exception('No existence.');
    }

    private function getByClassMap($name, $arguments)
    {

        foreach ($this->classMap as $className) {
            if (class_exists($className)) {
                $methods = get_class_methods($className);
                if (in_array($name, $methods)) {
                    $instance = new $className();
                    return call_user_func_array(array($instance, $name), $arguments);
                }
            }

            throw new \Exception('No existence.');
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     * @since 6.1.0
     */
    private function getDeprecatedResponse($name, $arguments)
    {
        if (method_exists($this->DEPRECATED, $name)) {
            return call_user_func_array(array($this->DEPRECATED, $name), $arguments);
        }

        throw new \Exception('No existence.');
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     * @since 6.1.0
     */
    private function getStaticResponse($name, $arguments)
    {
        if (method_exists('TorneLIB\Module\Network\Statics', $name)) {
            return call_user_func_array(array('TorneLIB\Module\Network\Statics', $name), $arguments);
        }

        throw new \Exception(sprintf('No static method with name %s via %s.', $name, __CLASS__), 1);
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

        try {
            $return = $this->getStaticResponse($name, $arguments);
        } catch (\Exception $e) {
        }

        try {
            $return = $this->getDeprecatedResponse($name, $arguments);
        } catch (\Exception $e) {
        }

        if (substr($name, 0, 3) === 'get') {
            try {
                $return = $this->get($name);
            } catch (\Exception $e) {
            }
        }

        if (is_null($return)) {
            try {
                $return = $this->getByClassMap($name, $arguments);
            } catch (\Exception $e) {
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

    /**
     * @param $name
     * @return void|null
     * @throws \Exception
     * @since 6.1.0
     */
    public function __get($name)
    {
        $return = null;

        if (!isset($this->{$name}) && isset($this->DEPRECATED->{$name})) {
            $return = $this->DEPRECATED->{$name};
            if (is_null($return)) {
                // Immediately return if still null.
                return $return;
            }
        }

        if (is_null($return)) {
            throw new \Exception(sprintf(
                'Variable "%s" for %s does not exist or has been deprecated',
                $name,
                __CLASS__
            ), 1);
        }

        return $return;
    }
}
