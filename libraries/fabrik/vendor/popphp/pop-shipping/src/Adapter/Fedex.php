<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Shipping\Adapter;

/**
 * FedEx shipping adapter class
 *
 * @category   Pop
 * @package    Pop_Shipping
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.1.0
 */
class Fedex extends AbstractAdapter
{

    /**
     * SOAP Client
     * @var \SoapClient
     */
    protected $client = null;

    /**
     * FedEx WSDL File
     * @var string
     */
    protected $wsdl = null;

    /**
     * Request array
     * @var array
     */
    protected $request = null;

    /**
     * Ship to fields
     * @var array
     */
    protected $shipTo = [
        'Contact' => [
            'PersonName'  => '',
            'CompanyName' => '',
            'PhoneNumber' => ''
        ],
        'Address' => [
            'StreetLines'         => [],
            'City'                => '',
            'StateOrProvinceCode' => '',
            'PostalCode'          => '',
            'CountryCode'         => '',
            'Residential'         => false
        ]
    ];

    /**
     * Ship from fields
     * @var array
     */
    protected $shipFrom = [
        'Contact' => [
            'PersonName'  => '',
            'CompanyName' => '',
            'PhoneNumber' => ''
        ],
        'Address' => [
            'StreetLines'         => [],
            'City'                => '',
            'StateOrProvinceCode' => '',
            'PostalCode'          => '',
            'CountryCode'         => ''
        ]
    ];

    /**
     * Package dimensions
     * @var array
     */
    protected $dimensions = [
        'Length' => null,
        'Width'  => null,
        'Height' => null,
        'Units'  => 'IN'
    ];

    /**
     * Package weight
     * @var array
     */
    protected $weight = [
        'Value' => null,
        'Units' => 'LB'
    ];

    /**
     * Services
     * @var array
     */
    protected static $services = [
        'FIRST_OVERNIGHT'     => '1st Overnight',
        'PRIORITY_OVERNIGHT'  => 'Priority Overnight',
        'STANDARD_OVERNIGHT'  => 'Standard Overnight',
        'FEDEX_2_DAY_AM'      => 'FedEx 2 Day AM',
        'FEDEX_2_DAY'         => 'FedEx 2 Day',
        'FEDEX_EXPRESS_SAVER' => 'FedEx Express Saver',
        'FEDEX_GROUND'        => 'FedEx Ground'
    ];

    /**
     * Constructor
     *
     * Method to instantiate an FedEx shipping adapter object
     *
     * @param  string $key
     * @param  string $password
     * @param  string $account
     * @param  string $meter
     * @param  string $wsdl
     * @return Fedex
     */
    public function __construct($key, $password, $account, $meter, $wsdl)
    {
        $this->wsdl = $wsdl;
        ini_set('soap.wsdl_cache_enabled', '0');

        $this->client = new \SoapClient($this->wsdl, ['trace' => 1]);

        $this->request['WebAuthenticationDetail'] = [
            'UserCredential' =>[
                'Key'      => $key,
                'Password' => $password
            ]
        ];

        $this->request['ClientDetail'] = [
            'AccountNumber' => $account,
            'MeterNumber'   => $meter
        ];

        $this->request['TransactionDetail'] = [
            'CustomerTransactionId' => ' *** Rate Request v14 using PHP ***'
        ];

        $this->request['Version'] = [
            'ServiceId'    => 'crs',
            'Major'        => '14',
            'Intermediate' => '0',
            'Minor'        => '0'
        ];

        $this->request['RequestedShipment']['RateRequestTypes'] = 'ACCOUNT';
        $this->request['RequestedShipment']['RateRequestTypes'] = 'LIST';
        $this->request['RequestedShipment']['PackageCount']     = '1';
    }

    /**
     * Static method to get the services
     *
     * @return array
     */
    public static function getServices()
    {
        return self::$services;
    }

    /**
     * Set ship to
     *
     * @param  array  $shipTo
     * @return mixed
     */
    public function shipTo(array $shipTo)
    {
        foreach ($shipTo as $key => $value) {
            if (stripos($key, 'person') !== false) {
                $this->shipTo['Contact']['PersonName'] = $value;
            } else if (stripos($key, 'company') !== false) {
                $this->shipTo['Contact']['CompanyName'] = $value;
            } else if (stripos($key, 'phone') !== false) {
                $this->shipTo['Contact']['PhoneNumber'] = $value;
            } else if (stripos($key, 'address') !== false) {
                $this->shipTo['Address']['StreetLines'][] = $value;
            } else if (strtolower($key) == 'city') {
                $this->shipTo['Address']['City'] = $value;
            } else if ((stripos($key, 'state') !== false) || (stripos($key, 'province') !== false)) {
                $this->shipTo['Address']['StateOrProvinceCode'] = $value;
            } else if ((strtolower($key) == 'postalcode') || (strtolower($key) == 'zipcode') || (strtolower($key) == 'zip')) {
                $this->shipTo['Address']['PostalCode'] = $value;
            } else if ((strtolower($key) == 'countrycode') || (strtolower($key) == 'country')) {
                $this->shipTo['Address']['CountryCode'] = $value;
            } else if (strtolower($key) == 'residential') {
                $this->shipTo['Address']['Residential'] = $value;
            }
        }

        $this->request['RequestedShipment']['Recipient'] = $this->shipTo;
    }

    /**
     * Set ship from
     *
     * @param  array  $shipFrom
     * @return mixed
     */
    public function shipFrom(array $shipFrom)
    {
        foreach ($shipFrom as $key => $value) {
            if (stripos($key, 'person') !== false) {
                $this->shipFrom['Contact']['PersonName'] = $value;
            } else if (stripos($key, 'company') !== false) {
                $this->shipFrom['Contact']['CompanyName'] = $value;
            } else if (stripos($key, 'phone') !== false) {
                $this->shipFrom['Contact']['PhoneNumber'] = $value;
            } else if (stripos($key, 'address') !== false) {
                $this->shipFrom['Address']['StreetLines'][] = $value;
            } else if (strtolower($key) == 'city') {
                $this->shipFrom['Address']['City'] = $value;
            } else if ((stripos($key, 'state') !== false) || (stripos($key, 'province') !== false)) {
                $this->shipFrom['Address']['StateOrProvinceCode'] = $value;
            } else if ((strtolower($key) == 'postalcode') || (strtolower($key) == 'zipcode') || (strtolower($key) == 'zip')) {
                $this->shipFrom['Address']['PostalCode'] = $value;
            } else if ((strtolower($key) == 'countrycode') || (strtolower($key) == 'country')) {
                $this->shipFrom['Address']['CountryCode'] = $value;
            } else if (strtolower($key) == 'residential') {
                $this->shipFrom['Address']['Residential'] = $value;
            }
        }

        $this->request['RequestedShipment']['Shipper'] = $this->shipFrom;
    }

    /**
     * Set dimensions
     *
     * @param  array  $dimensions
     * @param  string $unit
     * @return mixed
     */
    public function setDimensions(array $dimensions, $unit = null)
    {
        if ((null !== $unit) && (($unit == 'IN') || ($unit == 'CM'))) {
            $this->dimensions['Units'] = $unit;
        }

        foreach ($dimensions as $key => $value) {
            if (strtolower($key) == 'length') {
                $this->dimensions['Length'] = $value;
            } else if (strtolower($key) == 'width') {
                $this->dimensions['Width'] = $value;
            } else if (strtolower($key) == 'height') {
                $this->dimensions['Height'] = $value;
            }
        }
    }

    /**
     * Set dimensions
     *
     * @param  string $weight
     * @param  string $unit
     * @return mixed
     */
    public function setWeight($weight, $unit = null)
    {
        if ((null !== $unit) && (($unit == 'LB') || ($unit == 'KG'))) {
            $this->weight['Units'] = $unit;
        }

        $this->weight['Value'] = $weight;
    }

    /**
     * Send transaction
     *
     * @return void
     */
    public function send()
    {
        $this->request['RequestedShipment']['RequestedPackageLineItems'] = [
            'SequenceNumber'    => 1,
            'GroupPackageCount' => 1,
            'Weight'            => $this->weight
        ];

        if ((null !== $this->dimensions['Length']) &&
            (null !== $this->dimensions['Width']) &&
            (null !== $this->dimensions['Height'])) {
            $this->request['RequestedShipment']['RequestedPackageLineItems']['Dimensions'] = $this->dimensions;
        }

        $this->response = $this->client->getRates($this->request);
        $this->responseCode = (int)$this->response->Notifications->Code;
        $this->responseMessage = (string)$this->response->Notifications->Message;

        if ($this->responseCode == 0) {
            foreach ($this->response->RateReplyDetails as $rate) {
                $this->rates[self::$services[(string)$rate->ServiceType]] = number_format((string)$rate->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount, 2);
            }
            $this->rates = array_reverse($this->rates, true);
        }
    }

    /**
     * Return whether the transaction is a success
     *
     * @return boolean
     */
    public function isSuccess()
    {
        return ($this->responseCode == 0);
    }

    /**
     * Return whether the transaction is an error
     *
     * @return boolean
     */
    public function isError()
    {
        return ($this->responseCode != 0);
    }

}
