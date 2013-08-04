<?php
/**
 * Geocode Gmaps
 *
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * $$$ hugh - DEPRECATED - using ./libs/gmaps2.php for API v3 now.
 */

/**
 * GMaps class ver 0.2
 *
 * Gets geo-informations from the Google Maps API
 * http://code.google.com/apis/maps/index.html
 *
 * Copyright 2008-2009 by Enrico Zimuel (enrico@zimuel.it)
 *
 */
class GMaps
{
    const MAPS_HOST = 'maps.google.com';
    /**
     * Latitude
     *
     * @var double
     */
    private $_latitude;
    /**
     * Longitude
     *
     * @var double
     */
    private $_longitude;
    /**
     * Address
     *
     * @var string
     */
    private $_address;
    /**
     * Country name
     *
     * @var string
     */
    private $_countryName;
    /**
     * Country name code
     *
     * @var string
     */
    private $_countryNameCode;
    /**
     * Administrative area name
     *
     * @var string
     */
    private $_administrativeAreaName;
    /**
     * Postal Code
     *
     * @var string
     */
    private $_postalCode;
    /**
     * Google Maps Key
     *
     * @var string
     */
    private $_key;
    /**
     * Base Url
     *
     * @var string
     */
    private $_baseUrl;
    /**
     * Construct
     *
* @param string $key
     */
    function __construct ($key='')
    {
        $this->_key= $key;
        $this->_baseUrl= "http://" . self::MAPS_HOST . "/maps/geo?output=xml&key=" . $this->_key;
    }
    /**
     * getInfoLocation
     *
* @param string $address
* @param string $city
* @param string $state
     * @return boolean
     */
    public function getInfoLocation ($address) {
        if (!empty($address)) {
            return $this->_connect($address);
        }
        return false;
    }
    /**
     * connect to Google Maps
     *
* @param string $param
     * @return boolean
     */
    private function _connect($param) {
        $request_url = $this->_baseUrl . "&oe=utf-8&q=" . urlencode($param);
        $xml = simplexml_load_file($request_url);
        if (! empty($xml->Response)) {
            $point= $xml->Response->Placemark->Point;
            if (! empty($point)) {
                $coordinatesSplit = split(",", $point->coordinates);
                // Format: Longitude, Latitude, Altitude
                $this->_latitude = $coordinatesSplit[1];
                $this->_longitude = $coordinatesSplit[0];
            }
            $this->_address= $xml->Response->Placemark->address;
            $this->_countryName= $xml->Response->Placemark->AddressDetails->Country->CountryName;
            $this->_countryNameCode= $xml->Response->Placemark->AddressDetails->Country->CountryNameCode;
            $this->_administrativeAreaName= $xml->Response->Placemark->AddressDetails->Country->AdministrativeArea->AdministrativeAreaName;
            $administrativeArea= $xml->Response->Placemark->AddressDetails->Country->AdministrativeArea;
            if (!empty($administrativeArea->SubAdministrativeArea)) {
                $this->_postalCode= $administrativeArea->SubAdministrativeArea->Locality->PostalCode->PostalCodeNumber;
            } elseif (!empty($administrativeArea->Locality)) {
                $this->_postalCode= $administrativeArea->Locality->PostalCode->PostalCodeNumber;
            }
            return true;
        } else {
            return false;
        }
    }
    /**
     * get the Postal Code
     *
     * @return string
     */
    public function getPostalCode () {
        return $this->_postalCode;
    }
	/**
     * get the Address
     *
     * @return string
     */
    public function getAddress () {
        return $this->_address;
    }
	/**
     * get the Country name
     *
     * @return string
     */
    public function getCountryName () {
        return $this->_countryName;
    }
	/**
     * get the Country name code
     *
     * @return string
     */
    public function getCountryNameCode () {
        return $this->_countryNameCode;
    }
	/**
     * get the Administrative area name
     *
     * @return string
     */
    public function getAdministrativeAreaName () {
        return $this->_administrativeAreaName;
    }
    /**
     * get the Latitude coordinate
     *
     * @return double
     */
    public function getLatitude () {
        return $this->_latitude;
    }
    /**
     * get the Longitude coordinate
     *
     * @return double
     */
    public function getLongitude () {
        return $this->_longitude;
    }
}
?>
