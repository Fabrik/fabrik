<?php
namespace Clickatell\Api;

use Clickatell\Decoder;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

class ClickatellHttpTest extends PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $uri = "http/sendmsg";
        $args = array(
            'user'      => 'username',
            'password'  => 'password',
            'api_id'    => '123456'
        );

        $decoder = $this->getMockBuilder("Clickatell\Decoder")
            ->setMethods(array("unwrapLegacy"))
            ->disableOriginalConstructor()
            ->getMock();

        $decoder->expects($this->once())
            ->method('unwrapLegacy');

        $clickatell = $this->getMockBuilder('Clickatell\Api\ClickatellHttp')
            ->setMethods(array('curl'))
            ->setConstructorArgs(array('username', 'password', '123456'))
            ->getMock();

        $clickatell->expects($this->once())
            ->method('curl')
            ->with($uri, http_build_query($args))
            ->will($this->returnValue($decoder));

        $class = new ReflectionClass($clickatell);
        $method = $class->getMethod('get');
        $method->setAccessible(true);
        $method->invokeArgs($clickatell, array($uri, array()));
    }

    public function testSendMessage()
    {
        $default = array(
            'to'        => "12345,123456",
            'text'      => 'message',
            'mo'        => false,
            'callback'  => true
        );

        $clickatell = $this->getMockBuilder('Clickatell\Api\ClickatellHttp')
            ->setMethods(array('get'))
            ->disableOriginalConstructor()
            ->getMock();

        $response = new Decoder('ID: 123456789 To: 12345', 200);

        $clickatell->expects($this->once())
            ->method('get')
            ->with('http/sendmsg', $default)
            ->will($this->returnValue($response->unwrapLegacy()));

        $entries = $clickatell->sendMessage(array(12345, 123456), "message", array('mo' => false));

        $this->assertSame("123456789", $entries[0]->id);
        $this->assertEquals(12345, $entries[0]->destination);
        $this->assertSame(false, $entries[0]->errorCode);
        $this->assertSame(false, $entries[0]->error);
    }

    public function testGetBalance()
    {
        $return = array(
            'Credit' => 0.5
        );

        $clickatell = $this->getMockBuilder('Clickatell\Api\ClickatellHttp')
            ->setMethods(array('get'))
            ->disableOriginalConstructor()
            ->getMock();

        $clickatell->expects($this->once())
            ->method('get')
            ->with('http/getbalance', array())
            ->will($this->returnValue($return));

        $balance = $clickatell->getBalance();
        $this->assertSame($return['Credit'], $balance->balance);
    }

    public function testQueryMessage()
    {
        $clickatell = $this->getMockBuilder('Clickatell\Api\ClickatellHttp')
            ->setMethods(array('getMessageCharge'))
            ->disableOriginalConstructor()
            ->getMock();

        $clickatell->expects($this->once())
            ->method('getMessageCharge')
            ->with("12345");

        $clickatell->queryMessage("12345");
    }

    public function testGetMessageCharge()
    {
        $id = "12345";
        $return = array(
            'status' => "003",
            'charge' => 1.0
        );

        $clickatell = $this->getMockBuilder('Clickatell\Api\ClickatellHttp')
            ->setMethods(array('get'))
            ->disableOriginalConstructor()
            ->getMock();

        $clickatell->expects($this->once())
            ->method('get')
            ->with('http/getmsgcharge', array('apimsgid' => $id))
            ->will($this->returnValue($return));

        $response = $clickatell->getMessageCharge($id);

        $this->assertSame($id, $response->id);
        $this->assertSame($return['status'], $response->status);
        $this->assertSame($return['charge'], $response->charge);
    }

    public function testStopMessage()
    {
        $id = "12345";
        $return = array(
            "Status" => "003",
            "ID" => $id
        );

        $clickatell = $this->getMockBuilder('Clickatell\Api\ClickatellHttp')
            ->setMethods(array('get'))
            ->disableOriginalConstructor()
            ->getMock();

        $clickatell->expects($this->once())
            ->method('get')
            ->with('http/delmsg', array('apimsgid' => $id))
            ->will($this->returnValue($return));

        $response = $clickatell->stopMessage($id);

        $this->assertSame($id, $response->id);
        $this->assertSame($return['Status'], $response->status);
    }
}