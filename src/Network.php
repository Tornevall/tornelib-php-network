<?php

namespace TorneLIB;

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
        $this->NETWORK = new \TorneLIB\Module\Network();
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @since 6.1.0
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->NETWORK, $name), $arguments);
    }

    /**
     * @param $name
     * @since 6.1.0
     */
    public function __get($name)
    {
        return $this->NETWORK->{$name};
    }
}