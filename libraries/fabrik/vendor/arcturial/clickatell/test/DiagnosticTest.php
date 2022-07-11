<?php
namespace Clickatell;

use PHPUnit_Framework_TestCase;

class DiagnosticTest extends PHPUnit_Framework_TestCase
{
    public function testGetError()
    {
        $this->assertTrue(strlen(Diagnostic::getStatus("001")) > 0);
        $this->assertTrue(strlen(Diagnostic::getStatus("002")) > 0);
        $this->assertTrue(strlen(Diagnostic::getStatus("003")) > 0);
        $this->assertTrue(strlen(Diagnostic::getStatus("004")) > 0);
        $this->assertTrue(strlen(Diagnostic::getStatus("005")) > 0);
        $this->assertTrue(strlen(Diagnostic::getStatus("006")) > 0);
        $this->assertTrue(strlen(Diagnostic::getStatus("007")) > 0);
        $this->assertTrue(strlen(Diagnostic::getStatus("008")) > 0);
        $this->assertTrue(strlen(Diagnostic::getStatus("009")) > 0);
        $this->assertTrue(strlen(Diagnostic::getStatus("010")) > 0);
        $this->assertTrue(strlen(Diagnostic::getStatus("011")) > 0);
        $this->assertTrue(strlen(Diagnostic::getStatus("012")) > 0);
        $this->assertTrue(strlen(Diagnostic::getStatus("013")) > 0);
        $this->assertTrue(strlen(Diagnostic::getStatus("014")) > 0);
        $this->assertSame("unknown error", Diagnostic::getStatus("015"));
    }
}