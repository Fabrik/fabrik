<?php

// BitlinkTest.php
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
use PHPLicengine\Service\Bitlink;
use PHPUnit\Framework\TestCase;

class BitlinkTest extends TestCase
{

    public function testGetMetricsForBitlinkByReferrersByDomains()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/bitlinks/test/referrers_by_domains'),                    
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Bitlink($mock);
        $bitlink->getMetricsForBitlinkByReferrersByDomains('test', ['key' => 'value']);
    }    
    
    public function testGetMetricsForBitlinkByCountries()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/bitlinks/test/countries'),                    
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Bitlink($mock);
        $bitlink->getMetricsForBitlinkByCountries('test', ['key' => 'value']);
    }        

    public function testGetClicksForBitlink()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/bitlinks/test/clicks'),                    
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Bitlink($mock);
        $bitlink->getClicksForBitlink('test', ['key' => 'value']);
    }        

    public function testExpandBitlink()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('post')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/expand'),      
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Bitlink($mock);
        $bitlink->expandBitlink(['key' => 'value']);
    }        

    public function testGetMetricsForBitlinkByReferrers()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/bitlinks/test/referrers'),      
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Bitlink($mock);
        $bitlink->getMetricsForBitlinkByReferrers('test', ['key' => 'value']);
    }        

    public function testCreateFullBitlink()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('post')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/bitlinks'),
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Bitlink($mock);
        $bitlink->createFullBitlink(['key' => 'value']);
    }        

    public function testUpdateBitlink()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('patch')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/bitlinks/test'),
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Bitlink($mock);
        $bitlink->updateBitlink('test', ['key' => 'value']);
    }        

    public function testGetBitlink()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('https://api-ssl.bitly.com/v4/bitlinks/test'));
        $bitlink = new Bitlink($mock);
        $bitlink->getBitlink('test');
    }

    public function testGetClicksSummaryForBitlink()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo('https://api-ssl.bitly.com/v4/bitlinks/test/clicks/summary'),
                $this->identicalTo(['key' => 'value'])
            );
        $bitlink = new Bitlink($mock);
        $bitlink->getClicksSummaryForBitlink('test', ['key' => 'value']);
    }    
    
    public function testCreateBitlink()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('post')
            ->with(
                    $this->equalTo('https://api-ssl.bitly.com/v4/shorten'),
                    $this->identicalTo(['key' => 'value'])
                    );
        $bitlink = new Bitlink($mock);
        $bitlink->createBitlink(['key' => 'value']);
    }            

    public function testGetMetricsForBitlinkByReferringDomains()
    {
        $mock = $this->createMock(ApiInterface::class);
        $mock
            ->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo('https://api-ssl.bitly.com/v4/bitlinks/test/referring_domains'),
                $this->identicalTo(['key' => 'value'])
            );
        $bitlink = new Bitlink($mock);
        $bitlink->getMetricsForBitlinkByReferringDomains('test', ['key' => 'value']);
    }    

}
