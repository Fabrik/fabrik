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
 * USPS shipping adapter class
 *
 * @category   Pop
 * @package    Pop_Shipping
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.1.0
 */
class Usps extends AbstractAdapter
{

    /**
     * Live API URL
     * @var string
     */
    protected $liveUrl = 'http://production.shippingapis.com/ShippingAPI.dll?API=RateV4&XML=';

    /**
     * Test API URL
     * @var string
     */
    protected $testUrl = 'http://production.shippingapis.com/ShippingAPITest.dll?API=RateV4&XML=';

    /**
     * Test mode flag
     * @var boolean
     */
    protected $testMode = false;

    /**
     * Request XML
     * @var string
     */
    protected $request = '<RateV4Request USERID="[{username}]" PASSWORD="[{password}]">';

    /**
     * Ship to fields
     * @var array
     */
    protected $shipTo = [
        'ZipDestination' => null
    ];

    /**
     * Ship from fields
     * @var string
     */
    protected $shipFrom = [
        'ZipOrigination' => null
    ];

    /**
     * Container type
     * @var string
     */
    protected $container = 'RECTANGULAR';

    /**
     * Container size
     * @var string
     */
    protected $containerSize = 'REGULAR';

    /**
     * Machinable flag
     * @var string
     */
    protected $machinable = 'false';

    /**
     * Package dimensions
     * @var array
     */
    protected $dimensions = [
        'Width'  => null,
        'Length' => null,
        'Height' => null,
        'Girth'  => null
    ];

    /**
     * Package weight
     * @var array
     */
    protected $weight = [
        'Pounds' => 0,
        'Ounces' => 0
    ];

    /**
     * Constructor
     *
     * Method to instantiate an USPS shipping adapter object
     *
     * @param  string  $username
     * @param  string  $password
     * @param  boolean $test
     * @return Usps
     */
    public function __construct($username, $password, $test = false)
    {
        $this->testMode = (bool)$test;
        $this->request  = str_replace(['[{username}]', '[{password}]'], [$username, $password], $this->request);
    }

    /**
     * Set ship to
     *
     * @param  array $shipTo
     * @return void
     */
    public function shipTo(array $shipTo)
    {
        foreach ($shipTo as $key => $value) {
            if ((strtolower($key) == 'postalcode') || (strtolower($key) == 'zipcode') || (strtolower($key) == 'zip')) {
                $this->shipTo['ZipDestination'] = $value;
            }
        }
    }

    /**
     * Set ship from
     *
     * @param  array $shipFrom
     * @return void
     */
    public function shipFrom(array $shipFrom)
    {
        foreach ($shipFrom as $key => $value) {
            if ((strtolower($key) == 'postalcode') || (strtolower($key) == 'zipcode') || (strtolower($key) == 'zip')) {
                $this->shipFrom['ZipOrigination'] = $value;
            }
        }
    }

    /**
     * Set container
     *
     * @param  string $container
     * @throws Exception
     * @return void
     */
    public function setContainer($container = 'RECTANGULAR')
    {
        if (($container == 'RECTANGULAR') || ($container == 'NONRECTANGULAR')) {
            $this->container = $container;
        } else {
            throw new Exception('Error: The container type must be RECTANGULAR or NONRECTANGULAR.');
        }
    }

    /**
     * Set machinable flag
     *
     * @param  boolean $machinable
     * @return void
     */
    public function setMachinable($machinable = false)
    {
        $this->machinable = ($machinable) ? 'true' : 'false';
    }

    /**
     * Set dimensions
     *
     * @param  array  $dimensions
     * @param  string $unit
     * @return void
     */
    public function setDimensions(array $dimensions, $unit = null)
    {
        foreach ($dimensions as $key => $value) {
            if (strtolower($key) == 'length') {
                $this->dimensions['Length'] = $value;
            } else if (strtolower($key) == 'width') {
                $this->dimensions['Width'] = $value;
            } else if (strtolower($key) == 'height') {
                $this->dimensions['Height'] = $value;
            } else if (strtolower($key) == 'girth') {
                $this->dimensions['Girth'] = $value;
            }
        }

        if (max($this->dimensions) >= 12) {
            $this->containerSize = 'LARGE';
        }
    }

    /**
     * Set dimensions
     *
     * @param  string $weight
     * @param  string $unit
     * @return void
     */
    public function setWeight($weight, $unit = null)
    {
        if (is_float($weight)) {
            $lbs = (floor($weight));
            $ozs = round(16 * ($weight - floor($weight)), 2);
        } else {
            $lbs = $weight;
            $ozs = 0;
        }
        $this->weight['Pounds'] = $lbs;
        $this->weight['Ounces'] = $ozs;
    }

    /**
     * Send transaction
     *
     * @param  boolean $verifyPeer
     * @return void
     */
    public function send($verifyPeer = true)
    {
        $this->buildRequest();

        $options = [
            CURLOPT_HEADER         => false,
            CURLOPT_URL            => ((($this->testMode) ? $this->testUrl : $this->liveUrl) . rawurlencode($this->request)),
            CURLOPT_RETURNTRANSFER => true
        ];

        if (!$verifyPeer) {
            $options[CURLOPT_SSL_VERIFYPEER] = false;
        }

        $curl = curl_init();
        curl_setopt_array($curl, $options);

        $this->response = simplexml_load_string($this->parseResponse($curl));

        if (isset($this->response->Package)) {
            $this->responseCode = 1;
            foreach ($this->response->Package->Postage as $rate) {
                $this->rates[str_replace(['&lt;', '&gt;'], ['<', '>'], (string)$rate->MailService)] = (string)$rate->Rate;
            }
            $this->rates = array_reverse($this->rates, true);
        } else {
            if (isset($this->response->Number)) {
                $this->responseCode    = (string)$this->response->Number;
                $this->responseMessage = (string)$this->response->Description;
            } else {
                $this->responseCode = 0;
            }
        }
    }

    /**
     * Return whether the transaction is a success
     *
     * @return boolean
     */
    public function isSuccess()
    {
        return ($this->responseCode == 1);
    }

    /**
     * Return whether the transaction is an error
     *
     * @return boolean
     */
    public function isError()
    {
        return ($this->responseCode != 1);
    }

    /**
     * Build rate request
     *
     * @return void
     */
    protected function buildRequest()
    {
        $this->request .= PHP_EOL . '    <Package ID="1ST">';

        $this->request .= PHP_EOL . '        <Service>ALL</Service>';
        $this->request .= PHP_EOL . '        <ZipOrigination>' . $this->shipFrom['ZipOrigination'] . '</ZipOrigination>';
        $this->request .= PHP_EOL . '        <ZipDestination>' . $this->shipTo['ZipDestination'] . '</ZipDestination>';
        $this->request .= PHP_EOL . '        <Pounds>' . $this->weight['Pounds'] . '</Pounds>';
        $this->request .= PHP_EOL . '        <Ounces>' . $this->weight['Ounces'] . '</Ounces>';
        $this->request .= PHP_EOL . '        <Container>' . $this->container . '</Container>';
        $this->request .= PHP_EOL . '        <Size>' . $this->containerSize . '</Size>';

        if ((null !== $this->dimensions['Length']) &&
            (null !== $this->dimensions['Width']) &&
            (null !== $this->dimensions['Height'])) {
            $this->request .= PHP_EOL . '        <Width>' . $this->dimensions['Width'] . '</Width>';
            $this->request .= PHP_EOL . '        <Length>' . $this->dimensions['Length'] . '</Length>';
            $this->request .= PHP_EOL . '        <Height>' . $this->dimensions['Height'] . '</Height>';

            if (null == $this->dimensions['Girth']) {
                $this->dimensions['Girth'] = (2 * $this->dimensions['Width']) + (2 * $this->dimensions['Height']);
            }
            $this->request .= PHP_EOL . '        <Girth>' . $this->dimensions['Girth'] . '</Girth>';

        }

        $this->request .= PHP_EOL . '        <Machinable>' . $this->machinable . '</Machinable>';
        $this->request .= PHP_EOL . '        <DropOffTime>12:00</DropOffTime>';
        $this->request .= PHP_EOL . '        <ShipDate>' . date('Y-m-d') . '</ShipDate>';

        $this->request .= PHP_EOL . '    </Package>';
        $this->request .= PHP_EOL . '</RateV4Request>';
    }
}
