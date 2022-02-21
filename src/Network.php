<?php

/** @noinspection PhpCSValidationInspection */

namespace TorneLIB;

use TorneLIB\Module\Network;

/**
 * Class MODULE_NETWORK
 * @package TorneLIB
 * @version 6.1.4
 */
class MODULE_NETWORK
{
    /**
     * @var Module\Network Compatibility module.
     * @since 6.1.0
     */
    private $NETWORK;

    /**
     * Network constructor.
     * @since 6.1.0
     */
    public function __construct()
    {
        $this->NETWORK = new Network();
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @since 6.1.0
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->NETWORK, $name], $arguments);
    }

    /**
     * @param $name
     * @return void|null
     * @since 6.1.0
     */
    public function __get($name)
    {
        return $this->NETWORK->{$name};
    }
}
