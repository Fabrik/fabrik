<?php
namespace IPv4;

/**
 * Interface for aggregated network calculation reports
 *  - Associative array
 *  - JSON
 *  - String
 *  - Printed to STDOUT
 */
interface SubnetReportInterface
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
    public function createArrayReport(SubnetCalculator $sub);

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
    public function createJsonReport(SubnetCalculator $sub);

    /**
     * Print a report of subnet calculations.
     * Contains IP address, subnet mask, network portion and host portion.
     * Each of the above is provided in dotted quads, hexadecimal, and binary notation.
     * Also contains number of IP addresses and number of addressable hosts, IP address range, and broadcast address.
     *
     * @param SubnetCalculator $sub
     */
    public function printReport(SubnetCalculator $sub);

    /**
     * Print a report of subnet calculations
     * Contains IP address, subnet mask, network portion and host portion.
     * Each of the above is provided in dotted quads, hexadecimal, and binary notation.
     * Also contains number of IP addresses and number of addressable hosts, IP address range, and broadcast address.
     *
     * @param SubnetCalculator $sub
     *
     * @return string Subnet Calculator report
     */
    public function createPrintableReport(SubnetCalculator $sub);
}
