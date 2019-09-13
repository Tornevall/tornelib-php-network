<?php
/**
 * Created by PhpStorm.
 * User: thorne
 * Date: 1/30/19
 * Time: 3:21 PM
 */

use TorneLIB\MODULE_NETWORK;

class MODULE_NETWORKTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var MODULE_NETWORK
     */
    private $NETWORK;

    public function setUp()
    {
        $this->NETWORK = new TorneLIB\MODULE_NETWORK();
    }

    /**
     * @test
     */
    public function testGetProxyData()
    {
        print_r($this->NETWORK->getProxyData());
    }
}
