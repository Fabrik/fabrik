<?php
namespace IPv4;

/**
 * Network calculator for subnet mask and other classless (CIDR) network information.
 *
 * Given an IP address and CIDR network size, it calculates the following information:
 *   - IP address network subnet masks, network and host portions, and provides aggregated reports.
 *   - Subnet mask
 *   - Network portion
 *   - Host portion
 *   - Number of IP addresses in the network
 *   - Number of addressable hosts in the network
 *   - IP address range
 *   - Broadcast address
 *   - Min and max host
 *   - All IP addresses
 * Provides each data in dotted quads, hexadecimal, and binary formats, as well as array of quads.
 *
 * Aggregated network calculation reports:
 *  - Associative array
 *  - JSON
 *  - String
 *  - Printed to STDOUT
 */
class SubnetCalculator implements \JsonSerializable
{
    /** @var string IP address as dotted quads: xxx.xxx.xxx.xxx */
    private $ip_address;

    /** @var int CIDR network size */
    private $network_size;

    /** @var array of four elements containing the four quads of the IP address */
    private $quads = [];

    /** @var int Subnet mask in format used for subnet calculations */
    private $subnet_mask;

    /** @var SubnetReportInterface */
    private $report;

    const FORMAT_QUADS  = '%d';
    const FORMAT_HEX    = '%02X';
    const FORMAT_BINARY = '%08b';

    /**
     * Constructor - Takes IP address and network size, validates inputs, and assigns class attributes.
     * For example: 192.168.1.120/24 would be $ip = 192.168.1.120 $network_size = 24
     *
     * @param string                     $ip_address IP address in dotted quad notation.
     * @param int                        $network_size CIDR network size.
     * @param SubnetReportInterface|null $report
     */
    public function __construct($ip_address, $network_size, SubnetReportInterface $report = null)
    {
        $this->validateInputs($ip_address, $network_size);

        $this->ip_address   = $ip_address;
        $this->network_size = $network_size;
        $this->quads        = explode('.', $ip_address);
        $this->subnet_mask  = $this->calculateSubnetMask($network_size);
        $this->report       = $report ?: new SubnetReport();
    }

    /* **************** *
     * PUBLIC INTERFACE
     * **************** */

    /**
     * Get IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string IP address as dotted quads.
     */
    public function getIPAddress()
    {
        return $this->ip_address;
    }

    /**
     * Get IP address as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return array
     */
    public function getIPAddressQuads()
    {
        return $this->quads;
    }

    /**
     * Get IP address as hexadecimal
     *
     * @return string IP address in hex
     */
    public function getIPAddressHex()
    {
        return $this->ipAddressCalculation(self::FORMAT_HEX);
    }

    /**
     * Get IP address as binary
     *
     * @return string IP address in binary
     */
    public function getIPAddressBinary()
    {
        return $this->ipAddressCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get network size
     *
     * @return int network size
     */
    public function getNetworkSize()
    {
        return $this->network_size;
    }

    /**
     * Get the number of IP addresses in the network
     *
     * @return int Number of IP addresses
     */
    public function getNumberIPAddresses()
    {
        return pow(2, (32 - $this->network_size));
    }

    /**
     * Get the number of addressable hosts in the network
     *
     * @return int Number of IP addresses that are addressable
     */
    public function getNumberAddressableHosts()
    {
        if ($this->network_size == 32) {
            return 1;
        }
        if ($this->network_size == 31) {
            return 2;
        }

        return ($this->getNumberIPAddresses() - 2);
    }

    /**
     * Get range of IP addresses in the network
     *
     * @return array containing start and end of IP address range. IP addresses in dotted quad notation.
     */
    public function getIPAddressRange()
    {
        return [$this->getNetworkPortion(), $this->getBroadcastAddress()];
    }

    /**
     * Get range of IP addresses in the network
     *
     * @return array containing start and end of IP address range. IP addresses in dotted quad notation.
     */
    public function getAddressableHostRange()
    {
        return [$this->getMinHost(), $this->getMaxHost()];
    }

    /**
     * Get the broadcast IP address
     *
     * @return string IP address as dotted quads
     */
    public function getBroadcastAddress()
    {
        $network_quads         = $this->getNetworkPortionQuads();
        $number_ip_addresses   = $this->getNumberIPAddresses();

        $network_range_quads = [
            sprintf(self::FORMAT_QUADS, ($network_quads[0] & ($this->subnet_mask >> 24)) + ((($number_ip_addresses - 1) >> 24) & 0xFF)),
            sprintf(self::FORMAT_QUADS, ($network_quads[1] & ($this->subnet_mask >> 16)) + ((($number_ip_addresses - 1) >> 16) & 0xFF)),
            sprintf(self::FORMAT_QUADS, ($network_quads[2] & ($this->subnet_mask >>  8)) + ((($number_ip_addresses - 1) >>  8) & 0xFF)),
            sprintf(self::FORMAT_QUADS, ($network_quads[3] & ($this->subnet_mask >>  0)) + ((($number_ip_addresses - 1) >>  0) & 0xFF)),
        ];

        return implode('.', $network_range_quads);
    }

    /**
     * Get minimum host IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string min host as dotted quads
     */
    public function getMinHost()
    {
        if ($this->network_size === 32 || $this->network_size === 31) {
            return $this->ip_address;
        }
        return $this->minHostCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get minimum host IP address as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return array min host portion as dotted quads.
     */
    public function getMinHostQuads()
    {
        if ($this->network_size === 32 || $this->network_size === 31) {
            return $this->quads;
        }
        return explode('.', $this->minHostCalculation('%d', '.'));
    }

    /**
     * Get minimum host IP address as hex
     *
     * @return string min host portion as hex
     */
    public function getMinHostHex()
    {
        if ($this->network_size === 32 || $this->network_size === 31) {
            return implode('', array_map(
                function ($quad) {
                    return sprintf(self::FORMAT_HEX, $quad);
                },
                $this->quads
            ));
        }
        return $this->minHostCalculation(self::FORMAT_HEX);
    }

    /**
     * Get minimum host IP address as binary
     *
     * @return string min host portion as binary
     */
    public function getMinHostBinary()
    {
        if ($this->network_size === 32 || $this->network_size === 31) {
            return implode('', array_map(
                function ($quad) {
                    return sprintf(self::FORMAT_BINARY, $quad);
                },
                $this->quads
            ));
        }
        return $this->minHostCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get maximum host IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string max host as dotted quads.
     */
    public function getMaxHost()
    {
        if ($this->network_size === 32 || $this->network_size === 31) {
            return $this->ip_address;
        }
        return $this->maxHostCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get maximum host IP address as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return array min host portion as dotted quads
     */
    public function getMaxHostQuads()
    {
        if ($this->network_size === 32 || $this->network_size === 31) {
            return $this->quads;
        }
        return explode('.', $this->maxHostCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get maximum host IP address as hex
     *
     * @return string max host portion as hex
     */
    public function getMaxHostHex()
    {
        if ($this->network_size === 32 || $this->network_size === 31) {
            return implode('', array_map(
                function ($quad) {
                    return sprintf(self::FORMAT_HEX, $quad);
                },
                $this->quads
            ));
        }
        return $this->maxHostCalculation(self::FORMAT_HEX);
    }

    /**
     * Get maximum host IP address as binary
     *
     * @return string man host portion as binary
     */
    public function getMaxHostBinary()
    {
        if ($this->network_size === 32 || $this->network_size === 31) {
            return implode('', array_map(
                function ($quad) {
                    return sprintf(self::FORMAT_BINARY, $quad);
                },
                $this->quads
            ));
        }
        return $this->maxHostCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get subnet mask as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string subnet mask as dotted quads
     */
    public function getSubnetMask()
    {
        return $this->subnetCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get subnet mask as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return array of four elements containing the four quads of the subnet mask.
     */
    public function getSubnetMaskQuads()
    {
        return explode('.', $this->subnetCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get subnet mask as hexadecimal
     *
     * @return string subnet mask in hex
     */
    public function getSubnetMaskHex()
    {
        return $this->subnetCalculation(self::FORMAT_HEX);
    }

    /**
     * Get subnet mask as binary
     *
     * @return string subnet mask in binary
     */
    public function getSubnetMaskBinary()
    {
        return $this->subnetCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get network portion of IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string network portion as dotted quads
     */
    public function getNetworkPortion()
    {
        return $this->networkCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get network portion as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return array of four elements containing the four quads of the network portion
     */
    public function getNetworkPortionQuads()
    {
        return explode('.', $this->networkCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get network portion of IP address as hexadecimal
     *
     * @return string network portion in hex
     */
    public function getNetworkPortionHex()
    {
        return $this->networkCalculation(self::FORMAT_HEX);
    }

    /**
     * Get network portion of IP address as binary
     *
     * @return string network portion in binary
     */
    public function getNetworkPortionBinary()
    {
        return $this->networkCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get host portion of IP address as dotted quads: xxx.xxx.xxx.xxx
     *
     * @return string host portion as dotted quads
     */
    public function getHostPortion()
    {
        return $this->hostCalculation(self::FORMAT_QUADS, '.');
    }

    /**
     * Get host portion as array of quads: [xxx, xxx, xxx, xxx]
     *
     * @return array of four elements containing the four quads of the host portion
     */
    public function getHostPortionQuads()
    {
        return explode('.', $this->hostCalculation(self::FORMAT_QUADS, '.'));
    }

    /**
     * Get host portion of IP address as hexadecimal
     *
     * @return string host portion in hex
     */
    public function getHostPortionHex()
    {
        return $this->hostCalculation(self::FORMAT_HEX);
    }

    /**
     * Get host portion of IP address as binary
     *
     * @return string host portion in binary
     */
    public function getHostPortionBinary()
    {
        return $this->hostCalculation(self::FORMAT_BINARY);
    }

    /**
     * Get all IP addresses
     *
     * @return \Generator|string[]
     */
    public function getAllIPAddresses()
    {
        list($start_ip, $end_ip) = $this->getIPAddressRangeAsInts();

        for ($ip = $start_ip; $ip <= $end_ip; $ip++) {
            yield long2ip($ip);
        }
    }

    /**
     * Get all host IP addresses
     * Removes broadcast and network address if they exist.
     *
     * @return \Generator|string[]
     *
     * @throws \RuntimeException if there is an error in the IP address range calculation
     */
    public function getAllHostIPAddresses()
    {
        list($start_ip, $end_ip) = $this->getIPAddressRangeAsInts();

        if ($this->getNetworkSize() < 31) {
            $start_ip += 1;
            $end_ip   -= 1;
        }

        for ($ip = $start_ip; $ip <= $end_ip; $ip++) {
            yield long2ip($ip);
        }
    }

    /**
     * Is the IP address in the subnet?
     *
     * @param string $ip_address_string
     *
     * @return bool
     */
    public function isIPAddressInSubnet($ip_address_string)
    {
        $ip_address = ip2long($ip_address_string);
        list($start_ip, $end_ip) = $this->getIPAddressRangeAsInts();

        return $ip_address >= $start_ip && $ip_address <= $end_ip
            ? true
            : false;
    }

    /**
     * Get subnet calculations as an associated array
     *
     * @return array of subnet calculations
     */
    public function getSubnetArrayReport()
    {
        return $this->report->createArrayReport($this);
    }

    /**
     * Get subnet calculations as a JSON string
     *
     * @return string JSON string of subnet calculations
     *
     * @throws \RuntimeException if there is a JSON encode error
     */
    public function getSubnetJsonReport()
    {
        $json = $this->report->createJsonReport($this);

        if ($json === false) {
            throw new \RuntimeException('JSON report failure: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Print a report of subnet calculations
     */
    public function printSubnetReport()
    {
        $this->report->printReport($this);
    }

    /**
     * Print a report of subnet calculations
     *
     * @return string Subnet Calculator report
     */
    public function getPrintableReport()
    {
        return $this->report->createPrintableReport($this);
    }

    /**
     * String representation of a report of subnet calculations
     *
     * @return string
     */
    public function __toString()
    {
        return $this->report->createPrintableReport($this);
    }

    /* ************** *
     * PHP INTERFACES
     * ************** */

    /**
     * \JsonSerializable interface
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->report->createArrayReport($this);
    }

    /* ********************** *
     * PRIVATE IMPLEMENTATION
     * ********************** */

    /**
     * Calculate subnet mask
     *
     * @param  int $network_size
     *
     * @return int
     */
    private function calculateSubnetMask($network_size)
    {
        return 0xFFFFFFFF << (32 - $network_size);
    }

    /**
     * Calculate IP address for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted IP address
     */
    private function ipAddressCalculation($format, $separator = '')
    {
        return implode($separator, array_map(
            function ($quad) use ($format) {
                return sprintf($format, $quad);
            },
            $this->quads
        ));
    }

    /**
     * Subnet calculation
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string subnet
     */
    private function subnetCalculation($format, $separator = '')
    {
        $mask_quads = [
            sprintf($format, ($this->subnet_mask >> 24) & 0xFF),
            sprintf($format, ($this->subnet_mask >> 16) & 0xFF),
            sprintf($format, ($this->subnet_mask >>  8) & 0xFF),
            sprintf($format, ($this->subnet_mask >>  0) & 0xFF),
        ];

        return implode($separator, $mask_quads);
    }

    /**
     * Calculate network portion for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted subnet mask
     */
    private function networkCalculation($format, $separator = '')
    {
        $network_quads = [
            sprintf("$format", $this->quads[0] & ($this->subnet_mask >> 24)),
            sprintf("$format", $this->quads[1] & ($this->subnet_mask >> 16)),
            sprintf("$format", $this->quads[2] & ($this->subnet_mask >>  8)),
            sprintf("$format", $this->quads[3] & ($this->subnet_mask >>  0)),
        ];

        return implode($separator, $network_quads);
    }

    /**
     * Calculate host portion for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted subnet mask
     */
    private function hostCalculation($format, $separator = '')
    {
        $network_quads = [
            sprintf("$format", $this->quads[0] & ~($this->subnet_mask >> 24)),
            sprintf("$format", $this->quads[1] & ~($this->subnet_mask >> 16)),
            sprintf("$format", $this->quads[2] & ~($this->subnet_mask >>  8)),
            sprintf("$format", $this->quads[3] & ~($this->subnet_mask >>  0)),
        ];

        return implode($separator, $network_quads);
    }

    /**
     * Calculate min host for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted min host
     */
    private function minHostCalculation($format, $separator = '')
    {
        $network_quads = [
            sprintf("$format", $this->quads[0] & ($this->subnet_mask >> 24)),
            sprintf("$format", $this->quads[1] & ($this->subnet_mask >> 16)),
            sprintf("$format", $this->quads[2] & ($this->subnet_mask >>  8)),
            sprintf("$format", ($this->quads[3] & ($this->subnet_mask >> 0)) + 1),
        ];

        return implode($separator, $network_quads);
    }

    /**
     * Calculate max host for formatting
     *
     * @param string $format    sprintf format to determine if decimal, hex or binary
     * @param string $separator implode separator for formatting quads vs hex and binary
     *
     * @return string formatted max host
     */
    private function maxHostCalculation($format, $separator = '')
    {
        $network_quads         = $this->getNetworkPortionQuads();
        $number_ip_addresses   = $this->getNumberIPAddresses();

        $network_range_quads = [
            sprintf($format, ($network_quads[0] & ($this->subnet_mask >> 24)) + ((($number_ip_addresses - 1) >> 24) & 0xFF)),
            sprintf($format, ($network_quads[1] & ($this->subnet_mask >> 16)) + ((($number_ip_addresses - 1) >> 16) & 0xFF)),
            sprintf($format, ($network_quads[2] & ($this->subnet_mask >>  8)) + ((($number_ip_addresses - 1) >>  8) & 0xFF)),
            sprintf($format, ($network_quads[3] & ($this->subnet_mask >>  0)) + ((($number_ip_addresses - 1) >>  0) & 0xFE)),
        ];

        return implode($separator, $network_range_quads);
    }

    /**
     * Validate IP address and network
     *
     * @param string $ip_address   IP address in dotted quads format
     * @param int    $network_size Network size
     *
     * @throws \UnexpectedValueException IP or network size not valid
     */
    private function validateInputs($ip_address, $network_size)
    {
        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            throw new \UnexpectedValueException("IP address $ip_address not valid.");
        }
        if (($network_size < 1) || ($network_size > 32)) {
            throw new \UnexpectedValueException("Network size $network_size not valid.");
        }
    }

    /**
     * Get the start and end of the IP address range as ints
     *
     * @return array [start IP, end IP]
     */
    private function getIPAddressRangeAsInts()
    {
        list($start_ip, $end_ip) = $this->getIPAddressRange();
        $start_ip = ip2long($start_ip);
        $end_ip   = ip2long($end_ip);

        if ($start_ip === false || $end_ip === false) {
            throw new \RuntimeException('IP address range calculation failed: ' . print_r($this->getIPAddressRange(), true));
        }

        return [$start_ip, $end_ip];
    }
}
