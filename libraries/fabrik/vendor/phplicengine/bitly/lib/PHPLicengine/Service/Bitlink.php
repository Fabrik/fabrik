<?php

// Bitlink.php
#################################################
##
## PHPLicengine
##
#################################################
## Copyright 2009-{current_year} PHPLicengine
## 
## Licensed under the Apache License, Version 2.0 (the "License");
## you may not use this file except in compliance with the License.
## You may obtain a copy of the License at
##
##    http://www.apache.org/licenses/LICENSE-2.0
##
## Unless required by applicable law or agreed to in writing, software
## distributed under the License is distributed on an "AS IS" BASIS,
## WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
## See the License for the specific language governing permissions and
## limitations under the License.
#################################################

namespace PHPLicengine\Service;
use PHPLicengine\Exception\ResponseException;
use PHPLicengine\Exception\CurlException;
use PHPLicengine\Api\ApiInterface;

class Bitlink {
 
      private $url;
      private $api;      
      
      public function __construct (ApiInterface $api)
      {
             $this->api = $api;
             $this->url = 'https://api-ssl.bitly.com/v4';       
      }
      
      /*
      Get Metrics for a Bitlink by referrers by domain
      https://dev.bitly.com/v4/#operation/getMetricsForBitlinkByReferrersByDomains
      */
      public function getMetricsForBitlinkByReferrersByDomains(string $bitlink, array $params = array()) 
      {
             return $this->api->get($this->url . '/bitlinks/'.$bitlink.'/referrers_by_domains', $params);
      }
      
      /*
      Get Metrics for a Bitlink by countries
      https://dev.bitly.com/v4/#operation/getMetricsForBitlinkByCountries
      */             
      public function getMetricsForBitlinkByCountries(string $bitlink, array $params = array()) 
      {
             return $this->api->get($this->url . '/bitlinks/'.$bitlink.'/countries', $params);
      }

      /*
      Get Clicks for a Bitlink
      https://dev.bitly.com/v4/#operation/getClicksForBitlink
      */
      public function getClicksForBitlink(string $bitlink, array $params = array()) 
      {
             return $this->api->get($this->url . '/bitlinks/'.$bitlink.'/clicks', $params);
      }

      /*
      Expand a Bitlink
      https://dev.bitly.com/v4/#operation/expandBitlink
      */
      public function expandBitlink(array $params) 
      {
             return $this->api->post($this->url . '/expand', $params);
      }
      
      /*
      Get Metrics for a Bitlink by referrers
      https://dev.bitly.com/v4/#operation/getMetricsForBitlinkByReferrers
      */
      public function getMetricsForBitlinkByReferrers(string $bitlink, array $params = array()) 
      {
             return $this->api->get($this->url . '/bitlinks/'.$bitlink.'/referrers', $params);
      }
      
      /*
      Create a Bitlink
      https://dev.bitly.com/v4/#operation/createFullBitlink
      */
      public function createFullBitlink(array $params) 
      {
             return $this->api->post($this->url . '/bitlinks', $params);
      }
      
      /*
      Update a Bitlink
      https://dev.bitly.com/v4/#operation/updateBitlink
      */
      public function updateBitlink(string $bitlink, array $params) 
      {
             return $this->api->patch($this->url . '/bitlinks/'.$bitlink, $params);
      }

      /*
      Retrieve a Bitlink
      https://dev.bitly.com/v4/#operation/getBitlink
      */
      public function getBitlink(string $bitlink) 
      {
             return $this->api->get($this->url . '/bitlinks/'.$bitlink);
      }

      /*
      Get Clicks Summary for a Bitlink
      https://dev.bitly.com/v4/#operation/getClicksSummaryForBitlink
      */
      public function getClicksSummaryForBitlink(string $bitlink, array $params = array()) 
      {
             return $this->api->get($this->url . '/bitlinks/'.$bitlink.'/clicks/summary', $params);
      }

      /*
      Shorten a Link
      https://dev.bitly.com/v4/#operation/createBitlink
      */
      public function createBitlink(array $params) 
      {
             return $this->api->post($this->url . '/shorten', $params);
      }

      /*
      Get Metrics for a Bitlink by referring domains
      https://dev.bitly.com/v4/#operation/getMetricsForBitlinkByReferringDomains
      */
      public function getMetricsForBitlinkByReferringDomains(string $bitlink, array $params = array()) 
      {
             return $this->api->get($this->url . '/bitlinks/'.$bitlink.'/referring_domains', $params);
      }

}
