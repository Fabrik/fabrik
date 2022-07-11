<?php

// Organization.php
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

class Organization {
 
      private $url;
      private $api;      
      
      public function __construct (ApiInterface $api)
      {
             $this->api = $api;
             $this->url = 'https://api-ssl.bitly.com/v4/organizations';       
      }
 
      /*
      Retrieve Organizations
      https://dev.bitly.com/v4/#operation/getOrganizations
      */
      public function getOrganizations() 
      {
             return $this->api->get($this->url);
      }
      
      /*
      Retrieve Organization Shorten Counts
      https://dev.bitly.com/v4/#operation/getOrganizationShortenCounts
      */
      public function getOrganizationShortenCounts(string $organization_guid) 
      {
             return $this->api->get($this->url . '/'.$organization_guid.'/shorten_counts');
      }
      
      /*
      Retrieve an Organization
      https://dev.bitly.com/v4/#operation/getOrganization
      */
      public function getOrganization(string $organization_guid) 
      {
             return $this->api->get($this->url . '/'.$organization_guid);
      }      
 
}
