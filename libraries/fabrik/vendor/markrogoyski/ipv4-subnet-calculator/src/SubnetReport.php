<?php
namespace IPv4;

/**
 * SubnetReport implementation for aggregated network calculation reports
 *  - Associative array
 *  - JSON
 *  - String
 *  - Printed to STDOUT
 */
class SubnetReport implements SubnetReportInterface
{
    /**
     * Get subnet calculations as an associated array
     * Contains IP address, subnet mask, network portion and host portion.
     * Each of the above is provided in dotted quads, hexadecimal, and binary notation.
     * Also contains number of IP addresses and number of addressable hosts, IP address range, and broadcast address.
     *
     * @param SubnetCalculator $sub
     *
     * @return array of subnet calculations
     */
    public function createArrayReport(SubnetCalculator $sub)
    {
        return [
            'ip_address_with_network_size' => $sub->getIPAddress() . '/' . $sub->getNetworkSize(),
            'ip_address' => [
                'quads'  => $sub->getIPAddress(),
                'hex'    => $sub->getIPAddressHex(),
                'binary' => $sub->getIPAddressBinary()
            ],
            'subnet_mask' => [
                'quads'  => $sub->getSubnetMask(),
                'hex'    => $sub->getSubnetMaskHex(),
                'binary' => $sub->getSubnetMaskBinary()
            ],
            'network_portion' => [
                'quads'  => $sub->getNetworkPortion(),
                'hex'    => $sub->getNetworkPortionHex(),
                'binary' => $sub->getNetworkPortionBinary()
            ],
            'host_portion' => [
                'quads'  => $sub->getHostPortion(),
                'hex'    => $sub->getHostPortionHex(),
                'binary' => $sub->getHostPortionBinary()
            ],
            'network_size'                => $sub->getNetworkSize(),
            'number_of_ip_addresses'      => $sub->getNumberIPAddresses(),
            'number_of_addressable_hosts' => $sub->getNumberAddressableHosts(),
            'ip_address_range'            => $sub->getIPAddressRange(),
            'broadcast_address'           => $sub->getBroadcastAddress(),
            'min_host'                    => $sub->getMinHost(),
            'max_host'                    => $sub->getMaxHost(),
        ];
    }

    /**
     * Get subnet calculations as JSON string
     * Contains IP address, subnet mask, network portion and host portion.
     * Each of the above is provided in dotted quads, hexadecimal, and binary notation.
     * Also contains number of IP addresses and number of addressable hosts, IP address range, and broadcast address.
     *
     * @param SubnetCalculator $sub
     *
     * @return string|false JSON string of subnet calculations
     */
    public function createJsonReport(SubnetCalculator $sub)
    {
        return json_encode(self::createArrayReport($sub));
    }

    /**
     * Print a report of subnet calculations.
     * Contains IP address, subnet mask, network portion and host portion.
     * Each of the above is provided in dotted quads, hexadecimal, and binary notation.
     * Also contains number of IP addresses and number of addressable hosts, IP address range, and broadcast address.
     *
     * @param SubnetCalculator $sub
     */
    public function printReport(SubnetCalculator $sub)
    {
        print($sub);
    }

    /**
     * Print a report of subnet calculations
     * Contains IP address, subnet mask, network portion and host portion.
     * Each of the above is provided in dotted quads, hexadecimal, and binary notation.
     * Also contains number of IP addresses and number of addressable hosts, IP address range, and broadcast address.
     *
     * @param SubnetCalculator $sub
     *
     * @return string report of subnet calculations
     */
    public function createPrintableReport(SubnetCalculator $sub)
    {
        $string  = sprintf("%-18s %15s %8s %32s\n", "{$sub->getIPAddress()}/{$sub->getNetworkSize()}", 'Quads', 'Hex', 'Binary');
        $string .= sprintf("%-18s %15s %8s %32s\n", '------------------', '---------------', '--------', '--------------------------------');
        $string .= sprintf("%-18s %15s %8s %32s\n", 'IP Address:', $sub->getIPAddress(), $sub->getIPAddressHex(), $sub->getIPAddressBinary());
        $string .= sprintf("%-18s %15s %8s %32s\n", 'Subnet Mask:', $sub->getSubnetMask(), $sub->getSubnetMaskHex(), $sub->getSubnetMaskBinary());
        $string .= sprintf("%-18s %15s %8s %32s\n", 'Network Portion:', $sub->getNetworkPortion(), $sub->getNetworkPortionHex(), $sub->getNetworkPortionBinary());
        $string .= sprintf("%-18s %15s %8s %32s\n", 'Host Portion:', $sub->getHostPortion(), $sub->getHostPortionHex(), $sub->getHostPortionBinary());
        $string .= \PHP_EOL;
        $string .= sprintf("%-28s %d\n", 'Number of IP Addresses:', $sub->getNumberIPAddresses());
        $string .= sprintf("%-28s %d\n", 'Number of Addressable Hosts:', $sub->getNumberAddressableHosts());
        $string .= sprintf("%-28s %s\n", 'IP Address Range:', implode(' - ', $sub->getIPAddressRange()));
        $string .= sprintf("%-28s %s\n", 'Broadcast Address:', $sub->getBroadcastAddress());
        $string .= sprintf("%-28s %s\n", 'Min Host:', $sub->getMinHost());
        $string .= sprintf("%-28s %s\n", 'Max Host:', $sub->getMaxHost());

        return $string;
    }
}
