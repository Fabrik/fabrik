<?php

// CustomTest.php
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
use PHPLicengine\Service\Custom;
use PHPUnit\Framework\TestCase;

class CustomTest extends TestCase
{
    
    public function testUpdateCustomBitlink()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('patch')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/custom_bitlinks/test'),
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Custom($mock);
        $bitlink->updateCustomBitlink('test', ['key' => 'value']);
    } 
    
    public function testGetCustomBitlink()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/custom_bitlinks/test')
                    );
        $bitlink = new Custom($mock);
        $bitlink->getCustomBitlink('test');
    }     

    public function testAddCustomBitlink()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('post')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/custom_bitlinks'),
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Custom($mock);
        $bitlink->addCustomBitlink(['key' => 'value']);
    } 

    public function testGetCustomBitlinkMetricsByDestination()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/custom_bitlinks/test/clicks_by_destination')
                    );
        $bitlink = new Custom($mock);
        $bitlink->getCustomBitlinkMetricsByDestination('test');
    }     

}
