<?php

// CampaignTest.php
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

use PHPLicengine\Api\ApiInterface;
use PHPLicengine\Service\Campaign;
use PHPUnit\Framework\TestCase;

class CampaignTest extends TestCase
{
    
    public function testCreateChannel()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('post')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/channels'),
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Campaign($mock);
        $bitlink->createChannel(['key' => 'value']);
    } 
    
    public function testGetChannels()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/channels'),
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Campaign($mock);
        $bitlink->getChannels(['key' => 'value']);
    } 

    public function testCreateCampaign()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('post')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/campaigns'),
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Campaign($mock);
        $bitlink->createCampaign(['key' => 'value']);
    } 

    public function testGetCampaigns()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/campaigns'),
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Campaign($mock);
        $bitlink->getCampaigns(['key' => 'value']);
    } 
    
    public function testGetCampaign()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/campaigns/test')
                    );
        $bitlink = new Campaign($mock);
        $bitlink->getCampaign('test');
    } 

    public function testUpdateCampaign()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('patch')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/campaigns/test'),
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Campaign($mock);
        $bitlink->updateCampaign('test', ['key' => 'value']);
    } 

    public function testGetChannel()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/channels/test')
                    );
        $bitlink = new Campaign($mock);
        $bitlink->getChannel('test');
    } 

    public function testUpdateChannel()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('patch')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/channels/test'),
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Campaign($mock);
        $bitlink->updateChannel('test', ['key' => 'value']);
    } 

}
