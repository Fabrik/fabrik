pop-shipping
============

END OF LIFE
-----------
The `pop-shipping` component v2.1.0 is now end-of-life and will no longer be maintained.

[![Build Status](https://travis-ci.org/popphp/pop-shipping.svg?branch=master)](https://travis-ci.org/popphp/pop-shipping)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-shipping)](http://cc.popphp.org/pop-shipping/)

OVERVIEW
--------
`pop-shipping` is a component for calculating shipping rates with some of the known standard shipping
vendors like UPS, FedEx and the US Post Office. It can also be extended to support other shipping vendors
and their available APIs.

`pop-shipping` is a component of the [Pop PHP Framework](http://www.popphp.org/).

INSTALL
-------

Install `pop-shipping` using Composer.

    composer require popphp/pop-shipping

BASIC USAGE
-----------

### Creating a shipping object

Of course, using any of the shipping adapters will require you to have a registered account with
the shipping vendor:

##### FedEx

The FedEx API utilizes SOAP, so you'll have to obtain a copy of the WSDL file and
point to its location on your sever:

```php
use Pop\Shipping\Shipping;
use Pop\Shipping\Adapter\Fedex;

$shipping = new Shipping(
    new Fedex('USER_KEY', 'PASSWORD', 'ACCOUNT_NUM', 'METER_NUM', 'WSDL_FILE')
);
```

##### UPS

The UPS API utilizes basic XML under the hood:

```php
use Pop\Shipping\Shipping;
use Pop\Shipping\Adapter\Ups;

$shipping = new Shipping(
    new Ups('ACCESS_KEY', 'USER_ID', 'PASSWORD')
);
```

##### US Post Office

The US Post Office API utilizes basic XML under the hood as well:

```php
use Pop\Shipping\Shipping;
use Pop\Shipping\Adapter\Usps;

$shipping = new Shipping(
    new Usps('USERNAME', 'PASSWORD')
);
```

### Using the shipping object to get the rates

```php
use Pop\Shipping\Shipping;
use Pop\Shipping\Adapter\Ups;

$shipping = new Shipping(
    new Ups('ACCESS_KEY', 'USER_ID', 'PASSWORD')
);

// Set the 'ship to' address
$shipping->shipTo([
    'address' => '123 Main St.',
    'city'    => 'Some Town',
    'state'   => 'LA',
    'zip'     => '12345',
    'country' => 'US'
]);

// Set the 'ship from' address
$shipping->shipFrom([
    'company'  => 'Widgets Inc',
    'address1' => '456 Some St.',
    'address2' => 'Suite 100',
    'city'     => 'Some Town',
    'zip'      => '12345',
    'country'  => 'US'
]);

// Set the package dimensions
$shipping->setDimensions([
    'length' => 12,
    'height' => 10,
    'width'  => 8
], 'IN');

// Set the package weight
$shipping->setWeight(5.4, 'LBS');

// Go get the rates
$shipping->send();

if ($shipping->isSuccess()) {
    foreach ($shipping->getRates() as $service => $rate) {
        echo $service . ': ' . $rate . PHP_EOL;
    }
}
```

The above example will output something like:

    Next Day Air: $36.70
    2nd Day Air: $28.84
    3 Day Select: $22.25
    Ground: $17.48

