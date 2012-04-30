<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Services/IPPower.php';
require_once 'HTTP/Request2/Adapter/Mock.php';

class Services_IPPowerTest extends PHPUnit_Framework_TestCase
{
    protected $_response = null;
    protected $_ippower = null;
    protected $_http = null;

    public function setUp()
    {
        $this->_ippower = new Services_IPPower();
    }

    public function mockIPPower($data = '', $code = 200)
    {
        $text  = 'HTTP/1.1 ' . $code . " OK\n";
        $text .= "Connection: close\n\n";
        $text .= $data;

        $adapter = new HTTP_Request2_Adapter_Mock();
        $adapter->addResponse($text);
        $this->_http = new HTTP_Request2('http://foobar.com', 'GET', array('adapter' => $adapter));
        $this->_ippower->accept($this->_http);
    }

    public function tearDown()
    {
        $this->_ippower = null;
        $this->_http = null;
    }

    public function testSetPower()
    {
        $this->mockIPPower('<html>P60=1</html>');
        $this->assertTrue($this->_ippower->setPower(Services_IPPower::OUTLET_ONE, Services_IPPower::STATE_ON));
    }

    public function testGetPower()
    {
        $this->assertSame(1, $this->_ippower->getPower(1));
    }

    public function testSetSchedule()
    {
        $this->markTestIncomplete();
    }
}
