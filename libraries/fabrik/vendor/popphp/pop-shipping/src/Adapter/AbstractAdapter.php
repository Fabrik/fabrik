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
 * Shipping adapter abstract class
 *
 * @category   Pop
 * @package    Pop_Shipping
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.1.0
 */

abstract class AbstractAdapter implements AdapterInterface
{

    /**
     * Response object
     * @var object
     */
    protected $response = null;

    /**
     * Response code
     * @var int
     */
    protected $responseCode = null;

    /**
     * Response message
     * @var string
     */
    protected $responseMessage = null;

    /**
     * Service rates
     * @var array
     */
    protected $rates = [];

    /**
     * Send transaction
     *
     * @param  boolean $verifyPeer
     * @return void
     */
    abstract public function send($verifyPeer = true);

    /**
     * Return whether the transaction is a success
     *
     * @return boolean
     */
    abstract public function isSuccess();

    /**
     * Return whether the transaction is an error
     *
     * @return boolean
     */
    abstract public function isError();

    /**
     * Get response
     *
     * @return object
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get response code
     *
     * @return int
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * Get response message
     *
     * @return string
     */
    public function getResponseMessage()
    {
        return $this->responseMessage;
    }

    /**
     * Get service rates
     *
     * @return array
     */
    public function getRates()
    {
        return $this->rates;
    }

    /**
     * Parse the curl response
     *
     * @param  resource $curl
     * @return string
     */
    protected function parseResponse($curl)
    {
        $response = curl_exec($curl);

        if (curl_getinfo($curl, CURLOPT_HEADER)) {
            $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $body       = substr($response, $headerSize);
        } else {
            $body       = $response;
        }

        return $body;
    }

}
