<?php

namespace TorneLIB;

/**
 * Class MODULE_NETWORK
 * @package TorneLIB
 * @deprecated Do not use this directly.
 */
class MODULE_NETWORK
{
    private $network;

    public function __construct()
    {
        $this->network = new Module\Network();
    }

    /**
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        call_user_func_array(
            [
                $this->network,
                $name,
            ],
            $arguments
        );
    }

}