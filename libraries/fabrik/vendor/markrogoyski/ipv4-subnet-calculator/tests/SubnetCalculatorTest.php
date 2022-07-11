<?php
namespace IPv4\Tests;

use IPv4;

class SubnetCalculatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var IPv4\SubnetCalculator */
    private $sub;

    /**
     * Set up test SubnetCalculator
     */
    public function setUp()
    {
        $this->sub = new IPv4\SubnetCalculator('192.168.112.203', 23);
    }

    /**
     * @testCase     getIPAddress
     * @dataProvider dataProviderForIpAddresses
     * @param        string $given_ip_address
     * @param        int    $network_size
     */
    public function testGetIpAddress($given_ip_address, $network_size)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($given_ip_address, $network_size);

        // When
        $ip_address = $sub->getIPAddress();

        // Then
        $this->assertSame($given_ip_address, $ip_address);
    }

    /**
     * @testCase     getNetworkSize
     * @dataProvider dataProviderForIpAddresses
     * @param        string $ip_address
     * @param        int    $given_network_size
     */
    public function testGetNetworkSize($ip_address, $given_network_size)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $given_network_size);

        // When
        $network_size = $sub->getNetworkSize();

        // Then
        $this->assertSame($given_network_size, $network_size);
    }

    /**
     * @return array [ip_address, network_size]
     */
    public function dataProviderForIpAddresses()
    {
        return [
            ['192.168.112.203', 1],
            ['192.168.84.233', 2],
            ['10.10.122.113', 3],
            ['255.255.255.255', 4],
            ['192.168.112.207', 5],
            ['192.128.0.1', 6],
            ['128.0.0.0', 7],
            ['235.90.125.222', 8],
            ['208.153.158.185', 9],
            ['99.107.189.17', 10],
            ['233.126.142.167', 11],
            ['205.39.43.86', 12],
            ['158.114.74.115', 13],
            ['127.132.3.128', 14],
            ['243.73.87.101', 15],
            ['176.103.67.129', 16],
            ['190.113.28.0', 17],
            ['204.243.103.224', 18],
            ['203.247.20.148', 19],
            ['15.254.55.4', 20],
            ['96.245.55.29', 21],
            ['88.102.195.7', 22],
            ['144.60.195.68', 23],
            ['189.191.237.105', 24],
            ['98.79.29.150', 25],
            ['56.5.145.126', 26],
            ['80.170.127.173', 27],
            ['92.123.10.117', 28],
            ['88.52.155.198', 29],
            ['230.233.123.40', 30],
            ['254.17.211.42', 31],
            ['57.51.231.108', 32],
        ];
    }

    /**
     * @testCase     getIPAddressQuads
     * @dataProvider dataProviderForIpAddressQuads
     * @param        string $ip_address
     * @param        array $expected_quads
     */
    public function testGetIPAddressQuads($ip_address, array $expected_quads)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, 24);

        // When
        $quads = $sub->getIPAddressQuads();

        // Then
        $this->assertSame($expected_quads, $quads);
    }

    /**
     * @return array [ip_address, quads]
     */
    public function dataProviderForIpAddressQuads()
    {
        return [
            ['192.168.112.203', ['192', '168', '112', '203']],
            ['56.5.145.126', ['56', '5', '145', '126']],
            ['128.0.0.0', ['128', '0', '0', '0']],
        ];
    }

    /**
     * @testCase     getIPAddressHex
     * @dataProvider dataProviderForIpAddressHex
     * @param        string $ip_address
     * @param        string $expected_hex
     */
    public function testGetIPAddressHex($ip_address, $expected_hex)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, 24);

        // When
        $hex = $sub->getIPAddressHex();

        // Then
        $this->assertSame($expected_hex, $hex);
    }

    /**
     * @return array [ip_address, hex]
     */
    public function dataProviderForIpAddressHex()
    {
        return [
            ['192.168.112.203', 'C0A870CB'],
            ['56.5.145.126', '3805917E'],
            ['128.0.0.0', '80000000'],
        ];
    }

    /**
     * @testCase     getIPAddressBinary
     * @dataProvider dataProviderForIpAddressBinary
     * @param        string $ip_address
     * @param        string $expected_binary
     */
    public function testGetIPAddressBinary($ip_address, $expected_binary)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, 24);

        // When
        $binary = $sub->getIPAddressBinary();

        // Then
        $this->assertSame($expected_binary, $binary);
    }

    /**
     * @return array [ip_address, binary]
     */
    public function dataProviderForIpAddressBinary()
    {
        return [
            ['192.168.112.203', '11000000101010000111000011001011'],
            ['56.5.145.126', '00111000000001011001000101111110'],
            ['128.0.0.0', '10000000000000000000000000000000'],
        ];
    }

    /**
     * @testCase     getNumberIpAddresses returns the number of IP addresses
     * @dataProvider dataProviderForNumberOfAddresses
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        int    $expected_number_addresses
     */
    public function testGetNumberIPAddresses($ip_address, $network_size, $expected_number_addresses)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $number_addresses = $sub->getNumberIPAddresses();

        // Then
        $this->assertEquals($expected_number_addresses, $number_addresses);
    }

    /**
     * @return array [ip_address, network_size, number_addresses]
     */
    public function dataProviderForNumberOfAddresses()
    {
        return [
            ['192.168.112.203', 1,  2147483648],
            ['192.168.112.203', 2, 1073741824],
            ['192.168.112.203', 3, 536870912],
            ['192.168.112.203', 4, 268435456],
            ['192.168.112.203', 5, 134217728],
            ['192.168.112.203', 6, 67108864],
            ['192.168.112.203', 7, 33554432],
            ['192.168.112.203', 8, 16777216],
            ['192.168.112.203', 9, 8388608],
            ['192.168.112.203', 10, 4194304],
            ['192.168.112.203', 11, 2097152],
            ['192.168.112.203', 12, 1048576],
            ['192.168.112.203', 13, 524288],
            ['192.168.112.203', 14, 262144],
            ['192.168.112.203', 15, 131072],
            ['192.168.112.203', 16, 65536],
            ['192.168.112.203', 17, 32768],
            ['192.168.112.203', 18, 16384],
            ['192.168.112.203', 19, 8192],
            ['192.168.112.203', 20, 4096],
            ['192.168.112.203', 21, 2048],
            ['192.168.112.203', 22, 1024],
            ['192.168.112.203', 23, 512],
            ['192.168.112.203', 24, 256],
            ['192.168.112.203', 25, 128],
            ['192.168.112.203', 26, 64],
            ['192.168.112.203', 27, 32],
            ['192.168.112.203', 28, 16],
            ['192.168.112.203', 29, 8],
            ['192.168.112.203', 30, 4],
            ['192.168.112.203', 31, 2],
            ['192.168.112.203', 32, 1],
        ];
    }

    /**
     * @testCase     getNumberAddressableHosts returns the number of IP addresses
     * @dataProvider dataProviderForNumberOfAddressableHosts
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        int    $expected_number_addressable_hosts
     */
    public function testGetNumberAddressableHosts($ip_address, $network_size, $expected_number_addressable_hosts)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $number_addressable_hosts = $sub->getNumberAddressableHosts();

        // Then
        $this->assertEquals($expected_number_addressable_hosts, $number_addressable_hosts);
    }

    /**
     * @return array [ip_address, network_size, number_addressable_hosts]
     */
    public function dataProviderForNumberOfAddressableHosts()
    {
        return [
            ['192.168.112.203', 1, 2147483646],
            ['192.168.112.203', 2, 1073741822],
            ['192.168.112.203', 3, 536870910],
            ['192.168.112.203', 4, 268435454],
            ['192.168.112.203', 5, 134217726],
            ['192.168.112.203', 6, 67108862],
            ['192.168.112.203', 7, 33554430],
            ['192.168.112.203', 8, 16777214],
            ['192.168.112.203', 9, 8388606],
            ['192.168.112.203', 10, 4194302],
            ['192.168.112.203', 11, 2097150],
            ['192.168.112.203', 12, 1048574],
            ['192.168.112.203', 13, 524286],
            ['192.168.112.203', 14, 262142],
            ['192.168.112.203', 15, 131070],
            ['192.168.112.203', 16, 65534],
            ['192.168.112.203', 17, 32766],
            ['192.168.112.203', 18, 16382],
            ['192.168.112.203', 19, 8190],
            ['192.168.112.203', 20, 4094],
            ['192.168.112.203', 21, 2046],
            ['192.168.112.203', 22, 1022],
            ['192.168.112.203', 23, 510],
            ['192.168.112.203', 24, 254],
            ['192.168.112.203', 25, 126],
            ['192.168.112.203', 26, 62],
            ['192.168.112.203', 27, 30],
            ['192.168.112.203', 28, 14],
            ['192.168.112.203', 29, 6],
            ['192.168.112.203', 30, 2],
            ['192.168.112.203', 31, 2],
            ['192.168.112.203', 32, 1],
        ];
    }

    /**
     * @testCase     getIpAddressRange returns the lower and upper IP addresses in the range
     * @dataProvider dataProviderForIpAddressRange
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $expected_lower_ip
     * @param        string $expected_upper_ip
     */
    public function testGetIpAddressRange($ip_address, $network_size, $expected_lower_ip, $expected_upper_ip)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $lower_ip = $sub->getIPAddressRange()[0];
        $upper_ip = $sub->getIPAddressRange()[1];

        // Then
        $this->assertEquals($expected_lower_ip, $lower_ip);
        $this->assertEquals($expected_upper_ip, $upper_ip);
    }

    /**
     * @testCase     getNetworkPortion
     * @dataProvider dataProviderForIpAddressRange
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $expected_network_portion
     */
    public function testGetNetworkPortionLowerIp($ip_address, $network_size, $expected_network_portion)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $network_portion = $sub->getNetworkPortion();

        // Then
        $this->assertEquals($expected_network_portion, $network_portion);
    }

    /**
     * @return array [ip_address, network_size, lower_ip, upper_ip]
     */
    public function dataProviderForIpAddressRange()
    {
        return [
            ['192.168.112.203', 1, '128.0.0.0', '255.255.255.255'],
            ['192.168.112.203', 2, '192.0.0.0', '255.255.255.255'],
            ['192.168.112.203', 3, '192.0.0.0', '223.255.255.255'],
            ['192.168.112.203', 4, '192.0.0.0', '207.255.255.255'],
            ['192.168.112.203', 5, '192.0.0.0', '199.255.255.255'],
            ['192.168.112.203', 6, '192.0.0.0', '195.255.255.255'],
            ['192.168.112.203', 7, '192.0.0.0', '193.255.255.255'],
            ['192.168.112.203', 8, '192.0.0.0', '192.255.255.255'],
            ['192.168.112.203', 9, '192.128.0.0', '192.255.255.255'],
            ['192.168.112.203', 10, '192.128.0.0', '192.191.255.255'],
            ['192.168.112.203', 11, '192.160.0.0', '192.191.255.255'],
            ['192.168.112.203', 12, '192.160.0.0', '192.175.255.255'],
            ['192.168.112.203', 13, '192.168.0.0', '192.175.255.255'],
            ['192.168.112.203', 14, '192.168.0.0', '192.171.255.255'],
            ['192.168.112.203', 15, '192.168.0.0', '192.169.255.255'],
            ['192.168.112.203', 16, '192.168.0.0', '192.168.255.255'],
            ['192.168.112.203', 17, '192.168.0.0', '192.168.127.255'],
            ['192.168.112.203', 18, '192.168.64.0', '192.168.127.255'],
            ['192.168.112.203', 19, '192.168.96.0', '192.168.127.255'],
            ['192.168.112.203', 20, '192.168.112.0', '192.168.127.255'],
            ['192.168.112.203', 21, '192.168.112.0', '192.168.119.255'],
            ['192.168.112.203', 22, '192.168.112.0', '192.168.115.255'],
            ['192.168.112.203', 23, '192.168.112.0', '192.168.113.255'],
            ['192.168.112.203', 24, '192.168.112.0', '192.168.112.255'],
            ['192.168.112.203', 25, '192.168.112.128', '192.168.112.255'],
            ['192.168.112.203', 26, '192.168.112.192', '192.168.112.255'],
            ['192.168.112.203', 27, '192.168.112.192', '192.168.112.223'],
            ['192.168.112.203', 28, '192.168.112.192', '192.168.112.207'],
            ['192.168.112.203', 29, '192.168.112.200', '192.168.112.207'],
            ['192.168.112.203', 30, '192.168.112.200', '192.168.112.203'],
            ['192.168.112.203', 31, '192.168.112.202', '192.168.112.203'],
            ['192.168.112.203', 32, '192.168.112.203', '192.168.112.203'],
        ];
    }

    /**
     * @testCase     getAddressableHostRange returns the lower and upper addressable hosts
     * @dataProvider dataProviderForAddressableHostRange
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $expected_minHost
     * @param        string $expected_maxHost
     */
    public function testGetAddressableHostRange($ip_address, $network_size, $expected_minHost, $expected_maxHost)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $minHost = $sub->getAddressableHostRange()[0];
        $maxHost = $sub->getAddressableHostRange()[1];

        // Then
        $this->assertEquals($expected_minHost, $minHost);
        $this->assertEquals($expected_maxHost, $maxHost);
    }

    /**
     * @testCase     getBroadcastAddress returns the broadcast address
     * @dataProvider dataProviderForBroadcastAddress
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $expected_broadcast_address
     */
    public function testGetBroadcastAddress($ip_address, $network_size, $expected_broadcast_address)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $broadcast_address = $sub->getBroadcastAddress();

        // Then
        $this->assertEquals($expected_broadcast_address, $broadcast_address);
    }

    /**
     * @return array [ip_address, network_size, broadcast_address]
     */
    public function dataProviderForBroadcastAddress()
    {
        return [
            ['192.168.112.203', 1, '255.255.255.255'],
            ['192.168.112.203', 2, '255.255.255.255'],
            ['192.168.112.203', 3, '223.255.255.255'],
            ['192.168.112.203', 4, '207.255.255.255'],
            ['192.168.112.203', 5, '199.255.255.255'],
            ['192.168.112.203', 6, '195.255.255.255'],
            ['192.168.112.203', 7, '193.255.255.255'],
            ['192.168.112.203', 8, '192.255.255.255'],
            ['192.168.112.203', 9, '192.255.255.255'],
            ['192.168.112.203', 10, '192.191.255.255'],
            ['192.168.112.203', 11, '192.191.255.255'],
            ['192.168.112.203', 12, '192.175.255.255'],
            ['192.168.112.203', 13, '192.175.255.255'],
            ['192.168.112.203', 14, '192.171.255.255'],
            ['192.168.112.203', 15, '192.169.255.255'],
            ['192.168.112.203', 16, '192.168.255.255'],
            ['192.168.112.203', 17, '192.168.127.255'],
            ['192.168.112.203', 18, '192.168.127.255'],
            ['192.168.112.203', 19, '192.168.127.255'],
            ['192.168.112.203', 20, '192.168.127.255'],
            ['192.168.112.203', 21, '192.168.119.255'],
            ['192.168.112.203', 22, '192.168.115.255'],
            ['192.168.112.203', 23, '192.168.113.255'],
            ['192.168.112.203', 24, '192.168.112.255'],
            ['192.168.112.203', 25, '192.168.112.255'],
            ['192.168.112.203', 26, '192.168.112.255'],
            ['192.168.112.203', 27, '192.168.112.223'],
            ['192.168.112.203', 28, '192.168.112.207'],
            ['192.168.112.203', 29, '192.168.112.207'],
            ['192.168.112.203', 30, '192.168.112.203'],
            ['192.168.112.203', 31, '192.168.112.203'],
            ['192.168.112.203', 32, '192.168.112.203'],
        ];
    }

    /**
     * @testCase     getMinHost returns the lower addressable host
     * @dataProvider dataProviderForAddressableHostRange
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $expected_minHost
     */
    public function testGetMinHost($ip_address, $network_size, $expected_minHost)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $minHost = $sub->getMinHost();

        // Then
        $this->assertEquals($expected_minHost, $minHost);
    }

    /**
     * @testCase     getMaxHost returns the upper addressable host
     * @dataProvider dataProviderForAddressableHostRange
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $_
     * @param        string $expected_maxHost
     */
    public function testGetMaxHost($ip_address, $network_size, $_, $expected_maxHost)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $maxHost = $sub->getMaxHost();

        // Then
        $this->assertEquals($expected_maxHost, $maxHost);
    }

    /**
     * @return array [ip_address, network_size, minHost, maxHost]
     */
    public function dataProviderForAddressableHostRange()
    {
        return [
            ['192.168.112.203', 1, '128.0.0.1', '255.255.255.254'],
            ['192.168.112.203', 2, '192.0.0.1', '255.255.255.254'],
            ['192.168.112.203', 3, '192.0.0.1', '223.255.255.254'],
            ['192.168.112.203', 4, '192.0.0.1', '207.255.255.254'],
            ['192.168.112.203', 5, '192.0.0.1', '199.255.255.254'],
            ['192.168.112.203', 6, '192.0.0.1', '195.255.255.254'],
            ['192.168.112.203', 7, '192.0.0.1', '193.255.255.254'],
            ['192.168.112.203', 8, '192.0.0.1', '192.255.255.254'],
            ['192.168.112.203', 9, '192.128.0.1', '192.255.255.254'],
            ['192.168.112.203', 10, '192.128.0.1', '192.191.255.254'],
            ['192.168.112.203', 11, '192.160.0.1', '192.191.255.254'],
            ['192.168.112.203', 12, '192.160.0.1', '192.175.255.254'],
            ['192.168.112.203', 13, '192.168.0.1', '192.175.255.254'],
            ['192.168.112.203', 14, '192.168.0.1', '192.171.255.254'],
            ['192.168.112.203', 15, '192.168.0.1', '192.169.255.254'],
            ['192.168.112.203', 16, '192.168.0.1', '192.168.255.254'],
            ['192.168.112.203', 17, '192.168.0.1', '192.168.127.254'],
            ['192.168.112.203', 18, '192.168.64.1', '192.168.127.254'],
            ['192.168.112.203', 19, '192.168.96.1', '192.168.127.254'],
            ['192.168.112.203', 20, '192.168.112.1', '192.168.127.254'],
            ['192.168.112.203', 21, '192.168.112.1', '192.168.119.254'],
            ['192.168.112.203', 22, '192.168.112.1', '192.168.115.254'],
            ['192.168.112.203', 23, '192.168.112.1', '192.168.113.254'],
            ['192.168.112.203', 24, '192.168.112.1', '192.168.112.254'],
            ['192.168.112.203', 25, '192.168.112.129', '192.168.112.254'],
            ['192.168.112.203', 26, '192.168.112.193', '192.168.112.254'],
            ['192.168.112.203', 27, '192.168.112.193', '192.168.112.222'],
            ['192.168.112.203', 28, '192.168.112.193', '192.168.112.206'],
            ['192.168.112.203', 29, '192.168.112.201', '192.168.112.206'],
            ['192.168.112.203', 30, '192.168.112.201', '192.168.112.202'],
            ['192.168.112.203', 31, '192.168.112.203', '192.168.112.203'],
            ['192.168.112.203', 32, '192.168.112.203', '192.168.112.203'],
        ];
    }

    /**
     * @testCase     getMinHostQuads returns an array of quads
     * @dataProvider dataProviderForGetMinHostQuads
     * @param string $ip_address
     * @param int    $network_size
     * @param array  $expected_quads
     */
    public function testGetMinHostQuads($ip_address, $network_size, array $expected_quads)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $quads = $sub->getMinHostQuads();

        // Then
        $this->assertEquals($expected_quads, $quads);
    }

    /**
     * @return array
     */
    public function dataProviderForGetMinHostQuads()
    {
        return [
            ['192.168.112.203', 1, [128, 0, 0, 1]],
            ['192.168.112.203', 2, [192, 0, 0, 1]],
            ['192.168.112.203', 24, [192, 168, 112, 1]],
            ['192.168.112.203', 25, [192, 168, 112, 129]],
            ['192.168.112.203', 26, [192, 168, 112, 193]],
            ['192.168.112.203', 27, [192, 168, 112, 193]],
            ['192.168.112.203', 28, [192, 168, 112, 193]],
            ['192.168.112.203', 29, [192, 168, 112, 201]],
            ['192.168.112.203', 30, [192, 168, 112, 201]],
            ['192.168.112.203', 31, [192, 168, 112, 203]],
            ['192.168.112.203', 32, [192, 168, 112, 203]],
        ];
    }

    /**
     * @testCase     getMaxHostQuads returns an array of quads
     * @dataProvider dataProviderForGetMaxHostQuads
     * @param string $ip_address
     * @param int    $network_size
     * @param array  $expected_quads
     */
    public function testGetMaxHostQuads($ip_address, $network_size, array $expected_quads)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $quads = $sub->getMaxHostQuads();

        // Then
        $this->assertEquals($expected_quads, $quads);
    }

    /**
     * @return array
     */
    public function dataProviderForGetMaxHostQuads()
    {
        return [
            ['192.168.112.203', 1, [255, 255, 255, 254]],
            ['192.168.112.203', 2, [255, 255, 255, 254]],
            ['192.168.112.203', 24, [192, 168, 112, 254]],
            ['192.168.112.203', 25, [192, 168, 112, 254]],
            ['192.168.112.203', 26, [192, 168, 112, 254]],
            ['192.168.112.203', 27, [192, 168, 112, 222]],
            ['192.168.112.203', 28, [192, 168, 112, 206]],
            ['192.168.112.203', 29, [192, 168, 112, 206]],
            ['192.168.112.203', 30, [192, 168, 112, 202]],
            ['192.168.112.203', 31, [192, 168, 112, 203]],
            ['192.168.112.203', 32, [192, 168, 112, 203]],
        ];
    }

    /**
     * @testCase     getMinHostHex returns a string of hex
     * @dataProvider dataProviderForGetMinHostHex
     * @param string $ip_address
     * @param int    $network_size
     * @param string $expected_hex
     */
    public function testGetMinHostHex($ip_address, $network_size, $expected_hex)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $hex = $sub->getMinHostHex();

        // Then
        $this->assertEquals($expected_hex, $hex);
    }

    /**
     * @return array
     */
    public function dataProviderForGetMinHostHex()
    {
        return [
            ['192.168.112.203', 1, '80000001'],
            ['192.168.112.203', 2, 'C0000001'],
            ['192.168.112.203', 24, 'C0A87001'],
            ['192.168.112.203', 25, 'C0A87081'],
            ['192.168.112.203', 26, 'C0A870C1'],
            ['192.168.112.203', 27, 'C0A870C1'],
            ['192.168.112.203', 28, 'C0A870C1'],
            ['192.168.112.203', 29, 'C0A870C9'],
            ['192.168.112.203', 30, 'C0A870C9'],
            ['192.168.112.203', 31, 'C0A870CB'],
            ['192.168.112.203', 32, 'C0A870CB'],
        ];
    }

    /**
     * @testCase     getMaxHostHex returns a string of hex
     * @dataProvider dataProviderForGetMaxHostHex
     * @param string $ip_address
     * @param int    $network_size
     * @param string $expected_hex
     */
    public function testGetMaxHostHex($ip_address, $network_size, $expected_hex)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $hex = $sub->getMaxHostHex();

        // Then
        $this->assertEquals($expected_hex, $hex);
    }

    /**
     * @return array
     */
    public function dataProviderForGetMaxHostHex()
    {
        return [
            ['192.168.112.203', 1, 'FFFFFFFE'],
            ['192.168.112.203', 2, 'FFFFFFFE'],
            ['192.168.112.203', 24, 'C0A870FE'],
            ['192.168.112.203', 25, 'C0A870FE'],
            ['192.168.112.203', 26, 'C0A870FE'],
            ['192.168.112.203', 27, 'C0A870DE'],
            ['192.168.112.203', 28, 'C0A870CE'],
            ['192.168.112.203', 29, 'C0A870CE'],
            ['192.168.112.203', 30, 'C0A870CA'],
            ['192.168.112.203', 31, 'C0A870CB'],
            ['192.168.112.203', 32, 'C0A870CB'],
        ];
    }

    /**
     * @testCase     getMinHostBinary returns a string of binary
     * @dataProvider dataProviderForGetMinHostBinary
     * @param string $ip_address
     * @param int    $network_size
     * @param string $expected_binary
     */
    public function testGetMinHostBinary($ip_address, $network_size, $expected_binary)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $binary = $sub->getMinHostBinary();

        // Then
        $this->assertEquals($expected_binary, $binary);
    }

    /**
     * @return array
     */
    public function dataProviderForGetMinHostBinary()
    {
        return [
            ['192.168.112.203', 1, '10000000000000000000000000000001'],
            ['192.168.112.203', 2, '11000000000000000000000000000001'],
            ['192.168.112.203', 24, '11000000101010000111000000000001'],
            ['192.168.112.203', 25, '11000000101010000111000010000001'],
            ['192.168.112.203', 26, '11000000101010000111000011000001'],
            ['192.168.112.203', 27, '11000000101010000111000011000001'],
            ['192.168.112.203', 28, '11000000101010000111000011000001'],
            ['192.168.112.203', 29, '11000000101010000111000011001001'],
            ['192.168.112.203', 30, '11000000101010000111000011001001'],
            ['192.168.112.203', 31, '11000000101010000111000011001011'],
            ['192.168.112.203', 32, '11000000101010000111000011001011'],
        ];
    }

    /**
     * @testCase     getMaxHostBinary returns a string of binary
     * @dataProvider dataProviderForGetMaxHostBinary
     * @param string $ip_address
     * @param int    $network_size
     * @param string $expected_binary
     */
    public function testGetMaxHostBinary($ip_address, $network_size, $expected_binary)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $binary = $sub->getMaxHostBinary();

        // Then
        $this->assertEquals($expected_binary, $binary);
    }

    /**
     * @return array
     */
    public function dataProviderForGetMaxHostBinary()
    {
        return [
            ['192.168.112.203', 1, '11111111111111111111111111111110'],
            ['192.168.112.203', 2, '11111111111111111111111111111110'],
            ['192.168.112.203', 24, '11000000101010000111000011111110'],
            ['192.168.112.203', 25, '11000000101010000111000011111110'],
            ['192.168.112.203', 26, '11000000101010000111000011111110'],
            ['192.168.112.203', 27, '11000000101010000111000011011110'],
            ['192.168.112.203', 28, '11000000101010000111000011001110'],
            ['192.168.112.203', 29, '11000000101010000111000011001110'],
            ['192.168.112.203', 30, '11000000101010000111000011001010'],
            ['192.168.112.203', 31, '11000000101010000111000011001011'],
            ['192.168.112.203', 32, '11000000101010000111000011001011'],
        ];
    }

    /**
     * @testCase     getSubnetMask
     * @dataProvider dataProviderForSubnetMask
     * @param        int    $network_size
     * @param        string $subnet_mask
     * @param        array  $quads
     * @param        string $hex
     * @param        string $binary
     */
    public function testGetSubnetMask($network_size, $subnet_mask, array $quads, $hex, $binary)
    {
        // Given
        $sub = new IPv4\SubnetCalculator('192.168.233.207', $network_size);

        // Then
        $this->assertSame($subnet_mask, $sub->getSubnetMask());
        $this->assertSame($quads, $sub->getSubnetMaskQuads());
        $this->assertSame($hex, $sub->getSubnetMaskHex());
        $this->assertSame($binary, $sub->getSubnetMaskBinary());
    }

    /**
     * @return array [network size, subnet mask, hex, binary]
     */
    public function dataProviderForSubnetMask()
    {
        return [
            [1, '128.0.0.0', ['128', '0', '0', '0'], '80000000', '10000000000000000000000000000000'],
            [2, '192.0.0.0', ['192', '0', '0', '0'], 'C0000000', '11000000000000000000000000000000'],
            [3, '224.0.0.0', ['224', '0', '0', '0'], 'E0000000', '11100000000000000000000000000000'],
            [4, '240.0.0.0', ['240', '0', '0', '0'], 'F0000000', '11110000000000000000000000000000'],
            [5, '248.0.0.0', ['248', '0', '0', '0'], 'F8000000', '11111000000000000000000000000000'],
            [6, '252.0.0.0', ['252', '0', '0', '0'], 'FC000000', '11111100000000000000000000000000'],
            [7, '254.0.0.0', ['254', '0', '0', '0'], 'FE000000', '11111110000000000000000000000000'],
            [8, '255.0.0.0', ['255', '0', '0', '0'], 'FF000000', '11111111000000000000000000000000'],
            [9, '255.128.0.0', ['255', '128', '0', '0'], 'FF800000', '11111111100000000000000000000000'],
            [10, '255.192.0.0', ['255', '192', '0', '0'], 'FFC00000', '11111111110000000000000000000000'],
            [11, '255.224.0.0', ['255', '224', '0', '0'], 'FFE00000', '11111111111000000000000000000000'],
            [12, '255.240.0.0', ['255', '240', '0', '0'], 'FFF00000', '11111111111100000000000000000000'],
            [13, '255.248.0.0', ['255', '248', '0', '0'], 'FFF80000', '11111111111110000000000000000000'],
            [14, '255.252.0.0', ['255', '252', '0', '0'], 'FFFC0000', '11111111111111000000000000000000'],
            [15, '255.254.0.0', ['255', '254', '0', '0'], 'FFFE0000', '11111111111111100000000000000000'],
            [16, '255.255.0.0', ['255', '255', '0', '0'], 'FFFF0000', '11111111111111110000000000000000'],
            [17, '255.255.128.0', ['255', '255', '128', '0'], 'FFFF8000', '11111111111111111000000000000000'],
            [18, '255.255.192.0', ['255', '255', '192', '0'], 'FFFFC000', '11111111111111111100000000000000'],
            [19, '255.255.224.0', ['255', '255', '224', '0'], 'FFFFE000', '11111111111111111110000000000000'],
            [20, '255.255.240.0', ['255', '255', '240', '0'], 'FFFFF000', '11111111111111111111000000000000'],
            [21, '255.255.248.0', ['255', '255', '248', '0'], 'FFFFF800', '11111111111111111111100000000000'],
            [22, '255.255.252.0', ['255', '255', '252', '0'], 'FFFFFC00', '11111111111111111111110000000000'],
            [23, '255.255.254.0', ['255', '255', '254', '0'], 'FFFFFE00', '11111111111111111111111000000000'],
            [24, '255.255.255.0', ['255', '255', '255', '0'], 'FFFFFF00', '11111111111111111111111100000000'],
            [25, '255.255.255.128', ['255', '255', '255', '128'], 'FFFFFF80', '11111111111111111111111110000000'],
            [26, '255.255.255.192', ['255', '255', '255', '192'], 'FFFFFFC0', '11111111111111111111111111000000'],
            [27, '255.255.255.224', ['255', '255', '255', '224'], 'FFFFFFE0', '11111111111111111111111111100000'],
            [28, '255.255.255.240', ['255', '255', '255', '240'], 'FFFFFFF0', '11111111111111111111111111110000'],
            [29, '255.255.255.248', ['255', '255', '255', '248'], 'FFFFFFF8', '11111111111111111111111111111000'],
            [30, '255.255.255.252', ['255', '255', '255', '252'], 'FFFFFFFC', '11111111111111111111111111111100'],
            [31, '255.255.255.254', ['255', '255', '255', '254'], 'FFFFFFFE', '11111111111111111111111111111110'],
            [32, '255.255.255.255', ['255', '255', '255', '255'], 'FFFFFFFF', '11111111111111111111111111111111'],
        ];
    }

    /**
     * @testCase     getHostPortion
     * @dataProvider dataProviderForNetworkPortion
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $network
     * @param        array  $quads
     * @param        string $hex
     * @param        string $binary
     */
    public function testGetNetworkPortion($ip_address, $network_size, $network, array $quads, $hex, $binary)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // Then
        $this->assertSame($network, $sub->getNetworkPortion());
        $this->assertSame($quads, $sub->getNetworkPortionQuads());
        $this->assertSame($hex, $sub->getNetworkPortionHex());
        $this->assertSame($binary, $sub->getNetworkPortionBinary());
    }

    /**
     * @return array [ip_address, network_size, network, quads, hex, binary]
     */
    public function dataProviderForNetworkPortion()
    {
        return [
            ['192.168.112.203', 1, '128.0.0.0', ['128', '0', '0', '0'], '80000000', '10000000000000000000000000000000'],
            ['192.168.84.233', 2, '192.0.0.0', ['192', '0', '0', '0'], 'C0000000', '11000000000000000000000000000000'],
            ['10.10.122.113', 3, '0.0.0.0', ['0', '0', '0', '0'], '00000000', '00000000000000000000000000000000'],
            ['255.255.255.255', 4, '240.0.0.0', ['240', '0', '0', '0'], 'F0000000', '11110000000000000000000000000000'],
            ['192.168.112.207', 5, '192.0.0.0', ['192', '0', '0', '0'], 'C0000000', '11000000000000000000000000000000'],
            ['192.128.0.1', 6, '192.0.0.0', ['192', '0', '0', '0'], 'C0000000', '11000000000000000000000000000000'],
            ['128.0.0.0', 7, '128.0.0.0', ['128', '0', '0', '0'], '80000000', '10000000000000000000000000000000'],
            ['235.90.125.222', 8, '235.0.0.0', ['235', '0', '0', '0'], 'EB000000', '11101011000000000000000000000000'],
            ['208.153.158.185', 9, '208.128.0.0', ['208', '128', '0', '0'], 'D0800000', '11010000100000000000000000000000'],
            ['99.107.189.17', 10, '99.64.0.0', ['99', '64', '0', '0'], '63400000', '01100011010000000000000000000000'],
            ['233.126.142.167', 11, '233.96.0.0', ['233', '96', '0', '0'], 'E9600000', '11101001011000000000000000000000'],
            ['205.39.43.86', 12, '205.32.0.0', ['205', '32', '0', '0'], 'CD200000', '11001101001000000000000000000000'],
            ['158.114.74.115', 13, '158.112.0.0', ['158', '112', '0', '0'], '9E700000', '10011110011100000000000000000000'],
            ['127.132.3.128', 14, '127.132.0.0', ['127', '132', '0', '0'], '7F840000', '01111111100001000000000000000000'],
            ['243.73.87.101', 15, '243.72.0.0', ['243', '72', '0', '0'], 'F3480000', '11110011010010000000000000000000'],
            ['176.103.67.129', 16, '176.103.0.0', ['176', '103', '0', '0'], 'B0670000', '10110000011001110000000000000000'],
            ['190.113.28.0', 17, '190.113.0.0', ['190', '113', '0', '0'], 'BE710000', '10111110011100010000000000000000'],
            ['204.243.103.224', 18, '204.243.64.0', ['204', '243', '64', '0'], 'CCF34000', '11001100111100110100000000000000'],
            ['203.247.20.148', 19, '203.247.0.0', ['203', '247', '0', '0'], 'CBF70000', '11001011111101110000000000000000'],
            ['15.254.55.4', 20, '15.254.48.0', ['15', '254', '48', '0'], '0FFE3000', '00001111111111100011000000000000'],
            ['96.245.55.29', 21, '96.245.48.0', ['96', '245', '48', '0'], '60F53000', '01100000111101010011000000000000'],
            ['88.102.195.7', 22, '88.102.192.0', ['88', '102', '192', '0'], '5866C000', '01011000011001101100000000000000'],
            ['144.60.195.68', 23, '144.60.194.0', ['144', '60', '194', '0'], '903CC200', '10010000001111001100001000000000'],
            ['189.191.237.105', 24, '189.191.237.0', ['189', '191', '237', '0'], 'BDBFED00', '10111101101111111110110100000000'],
            ['98.79.29.150', 25, '98.79.29.128', ['98', '79', '29', '128'], '624F1D80', '01100010010011110001110110000000'],
            ['56.5.145.126', 26, '56.5.145.64', ['56', '5', '145', '64'], '38059140', '00111000000001011001000101000000'],
            ['80.170.127.173', 27, '80.170.127.160', ['80', '170', '127', '160'], '50AA7FA0', '01010000101010100111111110100000'],
            ['92.123.10.117', 28, '92.123.10.112', ['92', '123', '10', '112'], '5C7B0A70', '01011100011110110000101001110000'],
            ['88.52.155.198', 29, '88.52.155.192', ['88', '52', '155', '192'], '58349BC0', '01011000001101001001101111000000'],
            ['230.233.123.40', 30, '230.233.123.40', ['230', '233', '123', '40'], 'E6E97B28', '11100110111010010111101100101000'],
            ['254.17.211.42', 31, '254.17.211.42', ['254', '17', '211', '42'], 'FE11D32A', '11111110000100011101001100101010'],
            ['57.51.231.108', 32, '57.51.231.108', ['57', '51', '231', '108'], '3933E76C', '00111001001100111110011101101100'],
        ];
    }

    /**
     * @testCase     getHostPortion
     * @dataProvider dataProviderForHostPortion
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $host
     * @param        array  $quads
     * @param        string $hex
     * @param        string $binary
     */
    public function testGetHostPortion($ip_address, $network_size, $host, array $quads, $hex, $binary)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // Then
        $this->assertSame($host, $sub->getHostPortion());
        $this->assertSame($quads, $sub->getHostPortionQuads());
        $this->assertSame($hex, $sub->getHostPortionHex());
        $this->assertSame($binary, $sub->getHostPortionBinary());
    }

    /**
     * @return array [ip_address, network_size, host, quads, hex, binary]
     */
    public function dataProviderForHostPortion()
    {
        return [
            ['192.168.112.203', 1, '64.168.112.203', ['64', '168', '112', '203'], '40A870CB', '01000000101010000111000011001011'],
            ['192.168.84.233', 2, '0.168.84.233', ['0', '168', '84', '233'], '00A854E9', '00000000101010000101010011101001'],
            ['10.10.122.113', 3, '10.10.122.113', ['10', '10', '122', '113'], '0A0A7A71', '00001010000010100111101001110001'],
            ['255.255.255.255', 4, '15.255.255.255', ['15', '255', '255', '255'], '0FFFFFFF', '00001111111111111111111111111111'],
            ['192.168.112.207', 5, '0.168.112.207', ['0', '168', '112', '207'], '00A870CF', '00000000101010000111000011001111'],
            ['192.128.0.1', 6, '0.128.0.1', ['0', '128', '0', '1'], '00800001', '00000000100000000000000000000001'],
            ['128.0.0.0', 7, '0.0.0.0', ['0', '0', '0', '0'], '00000000', '00000000000000000000000000000000'],
            ['235.90.125.222', 8, '0.90.125.222', ['0', '90', '125', '222'], '005A7DDE', '00000000010110100111110111011110'],
            ['208.153.158.185', 9, '0.25.158.185', ['0', '25', '158', '185'], '00199EB9', '00000000000110011001111010111001'],
            ['99.107.189.17', 10, '0.43.189.17', ['0', '43', '189', '17'], '002BBD11', '00000000001010111011110100010001'],
            ['233.126.142.167', 11, '0.30.142.167', ['0', '30', '142', '167'], '001E8EA7', '00000000000111101000111010100111'],
            ['205.39.43.86', 12, '0.7.43.86', ['0', '7', '43', '86'], '00072B56', '00000000000001110010101101010110'],
            ['158.114.74.115', 13, '0.2.74.115', ['0', '2', '74', '115'], '00024A73', '00000000000000100100101001110011'],
            ['127.132.3.128', 14, '0.0.3.128', ['0', '0', '3', '128'], '00000380', '00000000000000000000001110000000'],
            ['243.73.87.101', 15, '0.1.87.101', ['0', '1', '87', '101'], '00015765', '00000000000000010101011101100101'],
            ['176.103.67.129', 16, '0.0.67.129', ['0', '0', '67', '129'], '00004381', '00000000000000000100001110000001'],
            ['190.113.28.0', 17, '0.0.28.0', ['0', '0', '28', '0'], '00001C00', '00000000000000000001110000000000'],
            ['204.243.103.224', 18, '0.0.39.224', ['0', '0', '39', '224'], '000027E0', '00000000000000000010011111100000'],
            ['203.247.20.148', 19, '0.0.20.148', ['0', '0', '20', '148'], '00001494', '00000000000000000001010010010100'],
            ['15.254.55.4', 20, '0.0.7.4', ['0', '0', '7', '4'], '00000704', '00000000000000000000011100000100'],
            ['96.245.55.29', 21, '0.0.7.29', ['0', '0', '7', '29'], '0000071D', '00000000000000000000011100011101'],
            ['88.102.195.7', 22, '0.0.3.7', ['0', '0', '3', '7'], '00000307', '00000000000000000000001100000111'],
            ['144.60.195.68', 23, '0.0.1.68', ['0', '0', '1', '68'], '00000144', '00000000000000000000000101000100'],
            ['189.191.237.105', 24, '0.0.0.105', ['0', '0', '0', '105'], '00000069', '00000000000000000000000001101001'],
            ['98.79.29.150', 25, '0.0.0.22', ['0', '0', '0', '22'], '00000016', '00000000000000000000000000010110'],
            ['56.5.145.126', 26, '0.0.0.62', ['0', '0', '0', '62'], '0000003E', '00000000000000000000000000111110'],
            ['80.170.127.173', 27, '0.0.0.13', ['0', '0', '0', '13'], '0000000D', '00000000000000000000000000001101'],
            ['92.123.10.117', 28, '0.0.0.5', ['0', '0', '0', '5'], '00000005', '00000000000000000000000000000101'],
            ['88.52.155.198', 29, '0.0.0.6', ['0', '0', '0', '6'], '00000006', '00000000000000000000000000000110'],
            ['230.233.123.40', 30, '0.0.0.0', ['0', '0', '0', '0'], '00000000', '00000000000000000000000000000000'],
            ['254.17.211.42', 31, '0.0.0.0', ['0', '0', '0', '0'], '00000000', '00000000000000000000000000000000'],
            ['57.51.231.108', 32, '0.0.0.0', ['0', '0', '0', '0'], '00000000', '00000000000000000000000000000000'],
        ];
    }

    /**
     * @testCase     constructor bad IP address
     * @dataProvider dataProviderForBadIpAddresses
     * @param        string $ip_address
     * @throws       \Exception
     */
    public function testConstructorExceptionOnBadIPAddress($ip_address)
    {
        // Then
        $this->expectException(\UnexpectedValueException::class);

        // When
        $sub = new IPv4\SubnetCalculator($ip_address, 24);
    }

    /**
     * @testCase     validateInputs bad IP address
     * @dataProvider dataProviderForBadIpAddresses
     * @param        string $ip_address
     * @throws       \Exception
     */
    public function testValidateInputExceptionOnBadIPAddress($ip_address)
    {
        // Given
        $validateInputs = new \ReflectionMethod(IPv4\SubnetCalculator::class, 'validateInputs');
        $validateInputs->setAccessible(true);

        // Then
        $this->expectException(\UnexpectedValueException::class);

        // When
        $validateInputs->invokeArgs($this->sub, [$ip_address, 24]);
    }

    /**
     * @return array [ip_address]
     */
    public function dataProviderForBadIpAddresses()
    {
        return [
            ['-1.168.3.4'],
            ['256.168.3.4'],
            ['555.444.333.222'],
        ];
    }

    /**
     * @testCase     constructor bad network size
     * @dataProvider dataProviderForBadNetworkSize
     * @param        int $network_size
     * @throws       \Exception
     */
    public function testConstructorExceptionOnBadNetworkSize($network_size)
    {
        // Then
        $this->expectException(\UnexpectedValueException::class);

        // When
        $sub = new IPv4\SubnetCalculator('192.168.112.203', $network_size);
    }


    /**
     * @testCase     validateInputs bad network size
     * @dataProvider dataProviderForBadNetworkSize
     * @param        int $network_size
     * @throws       \Exception
     */
    public function testValidateInputExceptionOnBadNetworkSize($network_size)
    {
        // Given
        $validateInputs = new \ReflectionMethod(IPv4\SubnetCalculator::class, 'validateInputs');
        $validateInputs->setAccessible(true);

        // Then
        $this->expectException(\UnexpectedValueException::class);

        // When
        $validateInputs->invokeArgs($this->sub, ['192.168.112.203', $network_size]);
    }

    /**
     * @return array [network_size]
     */
    public function dataProviderForBadNetworkSize()
    {
        return [
            [-2],
            [-1],
            [0],
            [33],
            [34],
            [89394839],
        ];
    }

    /**
     * @testCase getSubnetArrayReport
     */
    public function testGetSubnetArrayReport()
    {
        // When
        $report = $this->sub->getSubnetArrayReport();

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
     * @testCase getSubnetJsonReport
     */
    public function testGetSubnetJsonReport()
    {
        // When
        $json = $this->sub->getSubnetJsonReport();

        // Then
        $this->assertTrue(is_string($json));
    }

    /**
     * @testCase getSubnetJsonReport gets a JSON error from the SubnetReportInterface
     */
    public function testGetSubnetJsonReportJsonError()
    {
        // Given
        /** @var IPv4\SubnetReport|\PHPUnit_Framework_MockObject_MockObject $subnetReport */
        $subnetReport = $this->getMockBuilder(IPv4\SubnetReport::class)
            ->setMethods(['createJsonReport'])
            ->getMock();
        $subnetReport->method('createJsonReport')->willReturn(false);
        $sub = new IPv4\SubnetCalculator('192.168.112.203', 23, $subnetReport);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $sub->getSubnetJsonReport();
    }

    /**
     * @testCase printSubnetReport
     */
    public function testPrintSubnetReport()
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
        $this->sub->printSubnetReport();
    }

    /**
     * @testCase getPrintableReport
     */
    public function testGetPrintableReport()
    {
        // When
        $report = $this->sub->getPrintableReport();

        // Then
        $this->assertTrue(is_string($report));
    }

    /**
     * @testCase \JsonSerializable interface
     */
    public function testJsonSerializableInterface()
    {
        // When
        $json = json_encode($this->sub);

        // Then
        $this->assertInternalType('string', $json);

        // And
        $decoded = json_decode($json, true);
        $this->assertArrayHasKey('ip_address_with_network_size', $decoded);
        $this->assertArrayHasKey('ip_address', $decoded);
        $this->assertArrayHasKey('subnet_mask', $decoded);
        $this->assertArrayHasKey('network_portion', $decoded);
        $this->assertArrayHasKey('host_portion', $decoded);
        $this->assertArrayHasKey('network_size', $decoded);
        $this->assertArrayHasKey('number_of_ip_addresses', $decoded);
        $this->assertArrayHasKey('number_of_addressable_hosts', $decoded);
        $this->assertArrayHasKey('ip_address_range', $decoded);
        $this->assertArrayHasKey('broadcast_address', $decoded);
        $this->assertArrayHasKey('min_host', $decoded);
        $this->assertArrayHasKey('max_host', $decoded);
    }

    /**
     * @return array [ip_address, network_size, number_addresses]
     */
    public function dataProviderForGetAllIpsCount()
    {
        return [
            ['192.168.112.203', 16, 65536],
            ['192.168.112.203', 17, 32768],
            ['192.168.112.203', 18, 16384],
            ['192.168.112.203', 19, 8192],
            ['192.168.112.203', 20, 4096],
            ['192.168.112.203', 21, 2048],
            ['192.168.112.203', 22, 1024],
            ['192.168.112.203', 23, 512],
            ['192.168.112.203', 24, 256],
            ['192.168.112.203', 25, 128],
            ['192.168.112.203', 26, 64],
            ['192.168.112.203', 27, 32],
            ['192.168.112.203', 28, 16],
            ['192.168.112.203', 29, 8],
            ['192.168.112.203', 30, 4],
            ['192.168.112.203', 31, 2],
            ['192.168.112.203', 32, 1],
        ];
    }

    /**
     * @return array [ip_address, network_size, number_addresses]
     */
    public function dataProviderForGetAllIpsHostOnlyCount()
    {
        return [
            ['192.168.112.203', 16, 65534],
            ['192.168.112.203', 17, 32766],
            ['192.168.112.203', 18, 16382],
            ['192.168.112.203', 19, 8190],
            ['192.168.112.203', 20, 4094],
            ['192.168.112.203', 21, 2046],
            ['192.168.112.203', 22, 1022],
            ['192.168.112.203', 23, 510],
            ['192.168.112.203', 24, 254],
            ['192.168.112.203', 25, 126],
            ['192.168.112.203', 26, 62],
            ['192.168.112.203', 27, 30],
            ['192.168.112.203', 28, 14],
            ['192.168.112.203', 29, 6],
            ['192.168.112.203', 30, 2],
            ['192.168.112.203', 31, 2],
        ];
    }

    /**
     * @testCase     getAllIPAddresses returns the expected number of IP addresses
     * @dataProvider dataProviderForGetAllIpsCount
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        int    $number_addresses
     */
    public function testGetAllIPsCount($ip_address, $network_size, $number_addresses)
    {
        // Given
        $sub   = new IPv4\SubnetCalculator($ip_address, $network_size);
        $count = 0;

        // When
        foreach ($sub->getAllIPAddresses() as $ip) {
            $count++;
        }

        // Then
        $this->assertEquals($number_addresses, $count);
        $this->assertEquals($count, $sub->getNumberIPAddresses());
    }

    /**
     * @testCase     getAllHostIPAddresses returns the expected number of IP addresses
     * @dataProvider dataProviderForGetAllIpsHostOnlyCount
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        int    $number_addresses
     */
    public function testGetAllHostIPsCount($ip_address, $network_size, $number_addresses)
    {
        // Given
        $sub   = new IPv4\SubnetCalculator($ip_address, $network_size);
        $count = 0;

        // When
        foreach ($sub->getAllHostIPAddresses() as $ip) {
            $count++;
        }

        // Then
        $this->assertEquals($number_addresses, $count);
    }

    /**
     * @testCase getAllHostIPAddresses returns the expected number of IP addresses for edge case /32 network
     */
    public function testGetAllHostIPsCountHostsOnlyEdgeCaseSlash32Network()
    {
        // Given
        $sub   = new IPv4\SubnetCalculator('192.168.112.203', 32);
        $count = 0;

        // When
        foreach ($sub->getAllIPAddresses() as $ip) {
            $count++;
        }

        // Then
        $this->assertEquals(1, $count);
    }

    /**
     * @testCase     getAllIPAddresses returns the expected IP addresses
     * @dataProvider dataProviderForGetAllIps
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        array  $ip_addresses
     */
    public function testGetAllIPs($ip_address, $network_size, $ip_addresses)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        foreach ($sub->getAllIPAddresses() as $key => $ip) {
            // Then
            $this->assertEquals($ip_addresses[$key], $ip);
        }
    }

    /**
     * @return array [ip_address, network_size, [ip_addresses]]
     */
    public function dataProviderForGetAllIps()
    {
        return [
            ['192.168.112.203', 28, ['192.168.112.192', '192.168.112.193', '192.168.112.194', '192.168.112.195', '192.168.112.196', '192.168.112.197', '192.168.112.198', '192.168.112.199', '192.168.112.200', '192.168.112.201', '192.168.112.202', '192.168.112.203', '192.168.112.204', '192.168.112.205', '192.168.112.206', '192.168.112.207']],
            ['192.168.112.203', 29, ['192.168.112.200', '192.168.112.201', '192.168.112.202', '192.168.112.203', '192.168.112.204', '192.168.112.205', '192.168.112.206', '192.168.112.207']],
            ['192.168.112.203', 30, ['192.168.112.200', '192.168.112.201', '192.168.112.202', '192.168.112.203']],
            ['192.168.112.203', 31, ['192.168.112.202', '192.168.112.203']],
            ['192.168.112.203', 32, ['192.168.112.203']],
        ];
    }

    /**
     * @testCase     getAllHostIPAddresses returns the expected IP addresses
     * @dataProvider dataProviderForGetAllIpsHostsOnly
     * @param        string $ip_address
     * @param        int $network_size
     * @param        array $ip_addresses
     */
    public function testGetAllHostIPAddresses($ip_address, $network_size, $ip_addresses)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        foreach ($sub->getAllHostIPAddresses() as $key => $ip) {
            // Then
            $this->assertEquals($ip_addresses[$key], $ip);
        }
    }

    /**
     * @return array [ip_address, network_size, [ip_addresses]]
     */
    public function dataProviderForGetAllIpsHostsOnly()
    {
        return [
            ['192.168.112.203', 28, ['192.168.112.193', '192.168.112.194', '192.168.112.195', '192.168.112.196', '192.168.112.197', '192.168.112.198', '192.168.112.199', '192.168.112.200', '192.168.112.201', '192.168.112.202', '192.168.112.203', '192.168.112.204', '192.168.112.205', '192.168.112.206']],
            ['192.168.112.203', 29, ['192.168.112.201', '192.168.112.202', '192.168.112.203', '192.168.112.204', '192.168.112.205', '192.168.112.206']],
            ['192.168.112.203', 30, ['192.168.112.201', '192.168.112.202']],
            ['192.168.112.203', 31, ['192.168.112.202', '192.168.112.203',]],
            ['192.168.112.203', 32, ['192.168.112.203']],
        ];
    }

    /**
     * @testCase getAllIPAddresses gets an error in the getIPAddressRange calculation
     */
    public function testGetAllIPAddressesIPRangeCalculationError()
    {
        // Given
        /** @var IPv4\SubnetCalculator|\PHPUnit_Framework_MockObject_MockObject $sub */
        $sub = $this->getMockBuilder(IPv4\SubnetCalculator::class)
            ->setMethods(['getIPAddressRange'])
            ->disableOriginalConstructor()
            ->getMock();
        $sub->method('getIPAddressRange')->willReturn([-4, -1]);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        foreach ($sub->getAllIPAddresses() as $ip) {
            // Exception is thrown
        }
    }

    /**
     * @testCase getAllHostIPAddresses gets an error in the getIPAddressRange calculation
     */
    public function testGetAllHostIPAddressesIPRangeCalculationError()
    {
        // Given
        /** @var IPv4\SubnetCalculator|\PHPUnit_Framework_MockObject_MockObject $sub */
        $sub = $this->getMockBuilder(IPv4\SubnetCalculator::class)
            ->setMethods(['getIPAddressRange'])
            ->disableOriginalConstructor()
            ->getMock();
        $sub->method('getIPAddressRange')->willReturn([-4, -1]);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        foreach ($sub->getAllHostIPAddresses() as $ip) {
            // Exception is thrown
        }
    }

    /**
     * @testCase     isIPAddressInSubnet
     * @dataProvider dataProviderForGetAllIps
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        array  $ip_addresses
     */
    public function testIsIPAddressInSubnet($ip_address, $network_size, $ip_addresses)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        foreach ($ip_addresses as $ip_address) {
            // When
            $isIPInSubnet = $sub->isIPAddressInSubnet($ip_address);

            // Then
            $this->assertTrue($isIPInSubnet);
        }
    }

    /**
     * @testCase isIPAddressInSubnet for all IP addresses in a subnet
     */
    public function testIsIPAddressInSubnetForAllIPAddressesInSubnet()
    {
        foreach ($this->sub->getAllIPAddresses() as $ip_address) {
            // When
            $isIPInSubnet = $this->sub->isIPAddressInSubnet($ip_address);

            // Then
            $this->assertTrue($isIPInSubnet);
        }
    }

    /**
     * @testCase     isIPAddressInSubnet when it is not
     * @dataProvider dataProviderForIpAddressesNotInSubnet
     * @param        string $ip_address
     * @param        int    $network_size
     * @param        string $ip_address_to_check
     */
    public function testIsIpAddressInSubnetWhenItIsNot($ip_address, $network_size, $ip_address_to_check)
    {
        // Given
        $sub = new IPv4\SubnetCalculator($ip_address, $network_size);

        // When
        $isIPInSubnet = $sub->isIPAddressInSubnet($ip_address_to_check);

        // Then
        $this->assertFalse($isIPInSubnet, "$ip_address_to_check");
    }

    public function dataProviderForIpAddressesNotInSubnet()
    {
        return [
            ['192.168.112.203', 28, '10.168.112.194'],
            ['192.168.112.203', 28, '192.148.112.191'],
            ['192.168.112.203', 28, '192.168.111.191'],
            ['192.168.112.203', 28, '192.168.112.190'],
            ['192.168.112.203', 28, '192.168.112.191'],
            ['192.168.112.203', 28, '192.168.112.208'],
            ['192.168.112.203', 28, '192.168.112.209'],
            ['192.168.112.203', 28, '192.168.152.208'],
            ['192.168.112.203', 28, '192.178.112.208'],
            ['192.168.112.203', 28, '255.168.112.208'],
            ['192.168.112.203', 31, '192.168.112.201'],
            ['192.168.112.203', 31, '192.168.112.204'],
            ['192.168.112.203', 32, '192.168.112.202'],
            ['192.168.112.203', 32, '192.168.112.204'],

            ['192.168.112.203', 1, '127.0.0.0'],
            ['192.168.112.203', 2, '191.0.0.0'],
            ['192.168.112.203', 3, '191.0.0.0'],
            ['192.168.112.203', 4, '191.0.0.0'],
            ['192.168.112.203', 5, '191.0.0.0'],
            ['192.168.112.203', 6, '191.0.0.0'],
            ['192.168.112.203', 7, '190.0.0.0'],
            ['192.168.112.203', 8, '190.0.0.0'],
            ['192.168.112.203', 9, '190.128.0.0'],
            ['192.168.112.203', 10, '192.127.0.0'],
            ['192.168.112.203', 11, '192.150.0.0'],
            ['192.168.112.203', 12, '192.140.0.0'],
            ['192.168.112.203', 13, '192.167.0.0'],
            ['192.168.112.203', 14, '192.166.0.0'],
            ['192.168.112.203', 15, '192.165.0.0'],
            ['192.168.112.203', 16, '192.164.0.0'],
            ['192.168.112.203', 17, '192.163.0.0'],
            ['192.168.112.203', 18, '192.162.64.0'],
            ['192.168.112.203', 19, '192.161.96.0'],
            ['192.168.112.203', 20, '192.168.111.0'],
            ['192.168.112.203', 21, '192.168.110.0'],
            ['192.168.112.203', 22, '192.168.102.0'],
            ['192.168.112.203', 23, '192.168.102.0'],
            ['192.168.112.203', 24, '192.168.102.0'],
            ['192.168.112.203', 25, '192.168.102.128'],
            ['192.168.112.203', 26, '192.168.102.192'],
            ['192.168.112.203', 27, '192.168.102.192'],
            ['192.168.112.203', 28, '192.168.102.192'],
            ['192.168.112.203', 29, '192.168.111.200'],
            ['192.168.112.203', 30, '192.168.111.200'],
            ['192.168.112.203', 31, '192.168.111.202'],
            ['192.168.112.203', 32, '192.168.111.202'],

            ['192.168.112.203', 3, '224.255.255.255'],
            ['192.168.112.203', 4, '208.255.255.255'],
            ['192.168.112.203', 5, '200.255.255.255'],
            ['192.168.112.203', 6, '196.255.255.255'],
            ['192.168.112.203', 7, '194.255.255.255'],
            ['192.168.112.203', 8, '193.255.255.255'],
            ['192.168.112.203', 9, '194.255.255.255'],
            ['192.168.112.203', 10, '192.192.255.255'],
            ['192.168.112.203', 11, '192.193.255.255'],
            ['192.168.112.203', 12, '192.176.255.255'],
            ['192.168.112.203', 13, '192.177.255.255'],
            ['192.168.112.203', 14, '192.172.255.255'],
            ['192.168.112.203', 15, '192.179.255.255'],
            ['192.168.112.203', 16, '192.169.255.255'],
            ['192.168.112.203', 17, '192.178.127.255'],
            ['192.168.112.203', 18, '192.188.127.255'],
            ['192.168.112.203', 19, '192.198.127.255'],
            ['192.168.112.203', 20, '192.168.128.255'],
            ['192.168.112.203', 21, '192.168.129.255'],
            ['192.168.112.203', 22, '192.168.116.255'],
            ['192.168.112.203', 23, '192.168.114.255'],
            ['192.168.112.203', 24, '192.168.113.255'],
            ['192.168.112.203', 25, '192.168.113.255'],
            ['192.168.112.203', 26, '192.168.114.255'],
            ['192.168.112.203', 27, '192.168.112.224'],
            ['192.168.112.203', 28, '192.168.112.208'],
            ['192.168.112.203', 29, '192.168.112.208'],
            ['192.168.112.203', 30, '192.168.112.204'],
            ['192.168.112.203', 31, '192.168.112.205'],
            ['192.168.112.203', 32, '192.168.112.204'],
        ];
    }
}
