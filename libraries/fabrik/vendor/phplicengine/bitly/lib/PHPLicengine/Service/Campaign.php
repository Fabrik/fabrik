<?php

// Campaign.php
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

class Campaign {
 
      private $url;
      private $api;      
      
      public function __construct (ApiInterface $api)
      {
             $this->api = $api;
             $this->url = 'https://api-ssl.bitly.com/v4';       
      }
 
      /*
      Create Channel
      https://dev.bitly.com/v4/#operation/createChannel
      */
      public function createChannel(array $params)
      {
             return $this->api->post($this->url . '/channels', $params);
      }

      /*
      Retrieve Channels
      https://dev.bitly.com/v4/#operation/getChannels
      */
      public function getChannels(array $params)
      {
             return $this->api->get($this->url . '/channels', $params);
      }
      
      /*
      Create Campaign
      https://dev.bitly.com/v4/#operation/createCampaign
      */
      public function createCampaign(array $params)
      {
             return $this->api->post($this->url . '/campaigns', $params);
      }
      
      /*
      Retrieve Campaigns
      https://dev.bitly.com/v4/#operation/getCampaigns
      */
      public function getCampaigns(array $params)
      {
             return $this->api->get($this->url . '/campaigns', $params);
      }
      
      /*
      Retrieve a Campaign
      https://dev.bitly.com/v4/#operation/getCampaign
      */
      public function getCampaign(string $campaign_guid)
      {
             return $this->api->get($this->url . '/campaigns/'.$campaign_guid);
      }
      
      /*
      Update Campaign
      https://dev.bitly.com/v4/#operation/updateCampaign
      */
      public function updateCampaign(string $campaign_guid, array $params)
      {
             return $this->api->patch($this->url . '/campaigns/'.$campaign_guid, $params);
      }
      
      /*
      Get A Channel
      https://dev.bitly.com/v4/#operation/getChannel
      */
      public function getChannel(string $channel_guid)
      {
             return $this->api->get($this->url . '/channels/'.$channel_guid);
      }
      
      /*
      Update A Channel
      https://dev.bitly.com/v4/#operation/updateChannel
      */
      public function updateChannel(string $channel_guid, array $params)
      {
             return $this->api->patch($this->url . '/channels/'.$channel_guid, $params);
      }      
 
}
