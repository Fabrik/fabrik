[![Build Status](https://travis-ci.org/phplicengine/bitly.svg?branch=master)](https://travis-ci.org/phplicengine/bitly)
[![Latest Stable Version](https://img.shields.io/packagist/v/phplicengine/bitly?label=version)](https://packagist.org/packages/phplicengine/bitly)
[![Total Downloads](https://img.shields.io/packagist/dt/phplicengine/bitly?color=blue)](https://packagist.org/packages/phplicengine/bitly)
[![Release date](https://img.shields.io/github/release-date/phplicengine/bitly)](https://packagist.org/packages/phplicengine/bitly)
[![php](https://img.shields.io/packagist/php-v/phplicengine/bitly)](https://packagist.org/packages/phplicengine/bitly)
[![License](https://img.shields.io/packagist/l/phplicengine/bitly)](https://packagist.org/packages/phplicengine/bitly)



# Bitly API v4

## Contents
* [Installation](#installation)
* [Usage](#usage)
* [Manual](#manual)
* [Caching](#caching)
* [Contributing and Support](#contributing-and-support)
* [License](#license)

## Installation
```
composer require phplicengine/bitly
```

## Usage
```php
use PHPLicengine\Api\Api;
use PHPLicengine\Service\Bitlink;

$api = new API("API KEY GOES HERE");
$bitlink = new Bitlink($api);
$result = $bitlink->createBitlink(['long_url' => 'http://www.example.com']);

if ($api->isCurlError()) {
    
    print ($api->getCurlErrno().': '.$api->getCurlError());
    
} else {

    if ($result->isError()) {

        print("Error:<br />");
        print($result->getResponse());
        print($result->getDescription());
    
    } else {
    
       if ($result->isSuccess()) {
        
          print("SUCCESS:<br />");
          print($result->getResponse());
          print_r($result->getResponseArray());

        } else {

          print("FAIL:<br />");
          print($result->getResponse());
          print_r($result->getResponseArray());

        }
    }
}

print("INFO:<br />");

$resj = $result->getResponse();
print($resj."<br />");

$reso = $result->getResponseObject();
print_r($reso);

$resh = $result->getHeaders();
print_r($resh);

$resh = $api->getRequest();
print_r($resh);
```

## Manual

#### Service Classes

In [Bitly API v4](https://dev.bitly.com/v4/) documentations, resources are classified under serveral categories:

Bitlink, Group, Organization, User, Custom, Campaign, Bsd, App, Auth

We made each of them as a separate service class. Method names are the same as the last part of documentation url.
For example if you want to use [Get Metrics for a Bitlink by countries](https://dev.bitly.com/v4/#operation/getMetricsForBitlinkByCountries), this one is classified under Bitlink category in documentation and the last part of its url is `getMetricsForBitlinkByCountries`, so you can call it this way:

```php
use PHPLicengine\Api\Api;
use PHPLicengine\Service\Bitlink;

$api = new API("API KEY GOES HERE");
$bitlink = new Bitlink($api);
$result = $bitlink->getMetricsForBitlinkByCountries('bit.ly/34nRNvl', ['unit' => 'day', 'units' => -1]);
```

All Path parameters, must be passed as string in first argument of methods if necessary and all Query parameters must be passed as array in second argument of methods if necessary. If Path parameter is not needed, Query parameters will be first argument of methods.

Another example:

[Retrieve Group Shorten Counts](https://dev.bitly.com/v4/#operation/getGroupShortenCounts) is classified under Group category, and the last part of its link is `getGroupShortenCounts`, so you can call it this way:

```php
use PHPLicengine\Api\Api;
use PHPLicengine\Service\Group;

$api = new API("API KEY GOES HERE");
$bitlink = new Group($api);
$result = $bitlink->getGroupShortenCounts($group_guid);
```

Here is [list of available service classes and methods](Services.md).

#### Custom cURL Options

By default cURL timeout is 30. You can change it with:
```php
$api->setTimeout(30);
```

If you need to add some CURLOPT_* constants that are not enabled by default, you can call setCurlCallback() method to add them.

```php
use PHPLicengine\Api\Api;
use PHPLicengine\Service\Bitlink;

$api = new API("API KEY GOES HERE");
$api->setCurlCallback(function($ch, $params, $headers, $method) { 
      curl_setopt($ch, CURLOPT_*, 'some value'); 
}); 
$bitlink = new Bitlink($api);
```
This is added for your convenience, but you should not need it.

## Caching
Since Bitlinks never change or expire, this is recommended to cache data locally wherever possible. This library comes with Doctrine Cache. You can use cache like this:
```php
use PHPLicengine\Cache\Cache;
$factory = new Cache(['type' => 'file', 'path' => 'path/to/cache/folder']);
$cache = $factory->getCache();
$cache->save('key', 'value');
echo $cache->fetch('key'); // prints "value"
```
We suggest to look at [Doctrine Cache Doc](https://www.doctrine-project.org/projects/doctrine-cache/en/1.8/index.html) and investigate and customize [Cache class](lib/PHPLicengine/Cache.php) to use preferred cache type.

## Contributing and Support
For all issues or feature request or support questions please open a new [issue](https://github.com/phplicengine/bitly/issues). All pull requests are welcome.

## License
PHPLicengine Api is distributed under the Apache License. See [License](LICENSE).
