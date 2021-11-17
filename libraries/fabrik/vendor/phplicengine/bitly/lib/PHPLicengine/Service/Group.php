<?php

// Group.php
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

class Group {
 
      private $url;
      private $api;      
      
      public function __construct (ApiInterface $api)
      {
             $this->api = $api;
             $this->url = 'https://api-ssl.bitly.com/v4/groups';       
      }
 
      /*
      Retrieve Tags by Group
      https://dev.bitly.com/v4/#operation/getGroupTags
      */
      public function getGroupTags(string $group_guid) 
      {
             return $this->api->get($this->url . '/'.$group_guid.'/tags');
      }
      
      /*
      Get Click Metrics for a Group by referring networks
      https://dev.bitly.com/v4/#operation/GetGroupMetricsByReferringNetworks
      */
      public function getGroupMetricsByReferringNetworks(string $group_guid) 
      {
             return $this->api->get($this->url . '/'.$group_guid.'/referring_networks');
      }
      
      /*
      Retrieve Group Shorten Counts
      https://dev.bitly.com/v4/#operation/getGroupShortenCounts
      */
      public function getGroupShortenCounts(string $group_guid) 
      {
             return $this->api->get($this->url . '/'.$group_guid.'/shorten_counts');
      }

      /*
      Retrieve Groups
      https://dev.bitly.com/v4/#operation/getGroups
      */
      public function getGroups(array $params = array()) 
      {
             return $this->api->get($this->url, $params);
      }
      
      /*
      Retrieve Group Preferences
      https://dev.bitly.com/v4/#operation/getGroupPreferences
      */
      public function getGroupPreferences(string $group_guid) 
      {
             return $this->api->get($this->url . '/'.$group_guid.'/preferences');
      }
      
      /*
      Update Group Preferences
      https://dev.bitly.com/v4/#operation/updateGroupPreferences
      */
      public function updateGroupPreferences(string $group_guid, array $params) 
      {
             return $this->api->patch($this->url . '/'.$group_guid.'/preferences', $params);
      }

      /*
      Retrieve Bitlinks by Group
      https://dev.bitly.com/v4/#operation/getBitlinksByGroup
      */
      public function getBitlinksByGroup(string $group_guid, array $params = array()) 
      {
             return $this->api->get($this->url . '/'.$group_guid.'/bitlinks', $params);
      }

      /*
      Get Click Metrics for a Group by countries
      https://dev.bitly.com/v4/#operation/getGroupMetricsByCountries
      */
      public function getGroupMetricsByCountries(string $group_guid) 
      {
             return $this->api->get($this->url . '/'.$group_guid.'/countries');
      }
      
      /*
      Retrieve Sorted Bitlinks for Group
      https://dev.bitly.com/v4/#operation/getSortedBitlinks
      */
      public function getSortedBitlinks(string $group_guid, array $params = array(), string $sort = 'clicks') 
      {
             return $this->api->get($this->url . '/'.$group_guid.'/bitlinks/'.$sort, $params);
      }

      /*
      Update a Group
      https://dev.bitly.com/v4/#operation/updateGroup
      */
      public function updateGroup(string $group_guid, array $params) 
      {
             return $this->api->patch($this->url . '/'.$group_guid, $params);
      }      
      
      /*
      Retrieve a Group
      https://dev.bitly.com/v4/#operation/getGroup
      */
      public function getGroup(string $group_guid) 
      {
             return $this->api->get($this->url . '/'.$group_guid);
      }      
      
      /*
      Delete a Group
      https://dev.bitly.com/v4/#operation/deleteGroup
      */
      public function deleteGroup(string $group_guid) 
      {
             return $this->api->delete($this->url . '/'.$group_guid);
      }      
      
}
