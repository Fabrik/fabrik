<?php
namespace Clickatell;

use PHPUnit_Framework_TestCase;

class DecoderTest extends PHPUnit_Framework_TestCase
{
    public function testUnwrapLegacy()
    {
        $body = "ERR: 301, Some Error";
        $clickatell = new Decoder($body, 200);

        $this->setExpectedException('Exception', 'Some Error', 301);
        $return = $clickatell->unwrapLegacy();
    }

    public function testDecodeRest()
    {
        $body = json_encode(
            array(
                'data' => true
            )
        );

        $clickatell = new Decoder($body, 200);
        $return = $clickatell->decodeRest();
        $this->assertTrue($return);

        $body = json_encode(
            array(
                'error' => array(
                    'description' => 'Some Error',
                    'code' => 301
                )
            )
        );

        $clickatell = new Decoder($body, 200);
        $this->setExpectedException('Exception', 'Some Error', 301);
        $return = $clickatell->decodeRest();
    }
}