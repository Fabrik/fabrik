<?php
namespace Clickatell;

use PHPUnit_Framework_TestCase;
use ReflectionClass;

class ClickatellTest extends PHPUnit_Framework_TestCase
{
    public function testCurl()
    {
        $uri = "http/sendmsg";
        $args = "";

        $clickatell = $this->getMockForAbstractClass('Clickatell\Clickatell');

        $class = new ReflectionClass($clickatell);
        $method = $class->getMethod('curl');
        $method->setAccessible(true);
        $return = $method->invokeArgs($clickatell, array($uri, $args));

        $this->assertSame(200, $return->getStatus());
        $this->assertTrue(strlen($return->getBody()) > 0);
    }
}