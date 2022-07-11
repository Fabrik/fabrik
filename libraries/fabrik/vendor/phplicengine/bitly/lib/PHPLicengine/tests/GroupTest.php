<?php

// GroupTest.php
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
use PHPLicengine\Service\Group;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{

    public function testGetGroupTags()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/groups/test/tags')                    
                    );
        $bitlink = new Group($mock);
        $bitlink->getGroupTags('test');
    } 
    
    public function testGetGroupMetricsByReferringNetworks()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/groups/test/referring_networks')                    
                    );
        $bitlink = new Group($mock);
        $bitlink->getGroupMetricsByReferringNetworks('test');
    }     
    
    public function testGetGroupShortenCounts()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/groups/test/shorten_counts')                    
                    );
        $bitlink = new Group($mock);
        $bitlink->getGroupShortenCounts('test');
    }         

    public function testGetGroups()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/groups'),
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Group($mock);
        $bitlink->getGroups(['key' => 'value']);
    }         

    public function testGetGroupPreferences()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/groups/test/preferences')
                    );
        $bitlink = new Group($mock);
        $bitlink->getGroupPreferences('test');
    }         

    public function testUpdateGroupPreferences()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('patch')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/groups/test/preferences'),
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Group($mock);
        $bitlink->updateGroupPreferences('test', ['key' => 'value']);
    }        

    public function testGetBitlinksByGroup()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/groups/test/bitlinks'),
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Group($mock);
        $bitlink->getBitlinksByGroup('test', ['key' => 'value']);
    }         

    public function testGetGroupMetricsByCountries()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/groups/test/bitlinks')
                    );
        $bitlink = new Group($mock);
        $bitlink->getBitlinksByGroup('test');
    }         

    public function testGetSortedBitlinks()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/groups/test/bitlinks/clicks'),
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Group($mock);
        $bitlink->getSortedBitlinks('test', ['key' => 'value']);
    }         

    public function testUpdateGroup()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('patch')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/groups/test'),
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Group($mock);
        $bitlink->updateGroup('test', ['key' => 'value']);
    }        

    public function testGetGroup()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/groups/test')
                    );
        $bitlink = new Group($mock);
        $bitlink->getGroup('test');
    }         

    public function testDeleteGroup()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('delete')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/groups/test')
                    );
        $bitlink = new Group($mock);
        $bitlink->deleteGroup('test');
    }         

}
