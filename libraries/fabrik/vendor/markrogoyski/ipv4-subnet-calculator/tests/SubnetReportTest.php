<?php
namespace IPv4\Tests;

use IPv4;

class SubnetReportTest extends \PHPUnit\Framework\TestCase
{
    /** @var IPv4\SubnetCalculator */
    private $sub;

    /** @var IPv4\SubnetReport */
    private $report;

    /**
     * Set up
     */
    public function setUp()
    {
        $this->sub    = new IPv4\SubnetCalculator('192.168.112.203', 23);
        $this->report = new IPv4\SubnetReport();
    }

    /**
     * @testCase createArrayReport
     */
    public function testCreateArrayReport()
    {
        // When
        $report = $this->report->createArrayReport($this->sub);

        // Then
        $this->assertTrue(is_array($report));
        $this->assertArrayHasKey('ip_address_with_network_size', $report);
        $this->assertArrayHasKey('ip_address', $report);
        $this->assertArrayHasKey('subnet_mask', $report);
        $this->assertArrayHasKey('network_portion', $report);
        $this->assertArrayHasKey('host_portion', $report);
        $this->assertArrayHasKey('network_size', $report);
        $this->assertArrayHasKey('number_of_ip_addresses', $report);
        $this->assertArrayHasKey('number_of_addressable_hosts', $report);
        $this->assertArrayHasKey('ip_address_range', $report);
        $this->assertArrayHasKey('broadcast_address', $report);
        $this->assertArrayHasKey('min_host', $report);
        $this->assertArrayHasKey('max_host', $report);
    }

    /**
     * @testCase createJsonReport
     */
    public function testCreateJsonReport()
    {
        // When
        $json = $this->report->createJsonReport($this->sub);

        // Then
        $this->assertTrue(is_string($json));
    }

    /**
     * @testCase printReport
     */
    public function testPrintReport()
    {
        // Then
        $this->expectOutputRegex('
            /
                ^
                \d+[.]\d+[.]\d+[.]\d+\/\d+ \s+ Quads \s+ Hex \s+ Binary \n
                .+?                                                     \n
                IP [ ] Address:      .+                                 \n
                Subnet [ ] Mask:     .+                                 \n
                Network [ ] Portion: .+                                 \n
                Host [ ] Portion:    .+                                 \n
                                                                        \n
                Number [ ] of [ ] IP [ ] Addresses:      \s+ \d+        \n
                Number [ ] of [ ] Addressable [ ] Hosts: \s+ \d+        \n
                IP [ ] Address [ ] Range:                \s+ .+?        \n
                Broadcast [ ] Address:                   \s+ .+?        \n
                Min [ ] Host:                            \s  .+?        \n
                Max [ ] Host:                            \s  .+?        \n
                $
            /xms
        ');

        // When
        $this->report->printReport($this->sub);
    }

    /**
     * @testCase createPrintableReport
     */
    public function testCreatePrintableReport()
    {
        // When
        $report = $this->report->createPrintableReport($this->sub);

        // Then
        $this->assertTrue(is_string($report));
    }
}
