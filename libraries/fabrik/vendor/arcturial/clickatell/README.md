Clickatell SMS Messenger Library
================================

Master: [![Build Status](https://secure.travis-ci.org/arcturial/clickatell.png?branch=master)](http://travis-ci.org/arcturial/clickatell)

This library allows easy access to connecting the [Clickatell's](http://www.clickatell.com) different messenging API's.

1. Installation
------------------

This library uses [composer](http://www.getcomposer.org) and can be acquired using the following in your composer.json file.

``` json
{
    "require": {
        "arcturial/clickatell": "*"
    }
}
```

2. Usage
------------------

The library currently supports the `ClickatellHttp` and `ClickatellRest` adapters.

### HTTP API

``` php
use Clickatell\Api\ClickatellHttp;

$clickatell = new ClickatellHttp($username, $password, $apiID);
$response = $clickatell->sendMessage(array(1111111111), "My Message");

foreach ($response as $message) {
    echo $message->id;

    // Message response fields:
    // $message->id
    // $message->destination
    // $message->error
    // $message->errorCode
}

```

### REST API

``` php
use Clickatell\Api\ClickatellRest;

$clickatell = new ClickatellRest($token);
$response = $clickatell->sendMessage(array(1111111111), "My Message");

foreach ($response as $message) {
    echo $message->id;

    // Message response fields:
    // $message->id
    // $message->destination
    // $message->error
    // $message->errorCode
}

```

### Sending to multiple numbers

The `sendMessage` call `to` parameter can take an array of numbers. If you specify only a single number like `$clickatell->sendMessage(1111111111, "Message")` the library will automatically convert it to an array for your convenience.

3. Supported API calls
------------------

The available calls are defined in the `Clickatell\TransportInterface` interface.

``` php

public function sendMessage($to, $message, $extra = array());

public function getBalance();

public function stopMessage($apiMsgId);

public function queryMessage($apiMsgId);

public function routeCoverage($msisdn);

public function getMessageCharge($apiMsgId);

```


4. Events
---------------

The library comes with a `ClickatellEvent` class which is a wrapper for any of the other transports. This class
can assist you with debugging or logging API interactions.

This class uses the [Proxy Pattern](http://en.wikipedia.org/wiki/Proxy_pattern).

``` php
<?php

use Clickatell\Api\ClickatellHttp;
use Clickatell\ClickatellEvent;
use Clickatell\Event;

$clickatell = new ClickatellHttp($username, $password, $apiID);
$event = new ClickatellEvent($clickatell);

$event->onRequest(function ($event, $args) {

    var_dump($event);
    var_dump($args->to);
    var_dump($args->message);
    var_dump($args->extra);

    // The parameters in the event object depend on the type of event.
    // The event constants are available in the Clickatell\Event class.
});

$event->onResponse(function ($event, $obj) {

    var_dump($event);
    var_dump($obj);

    // The $obj variable is the same as the response you would get back. So
    // in the case of sendMessage it will be an array of message responses.
});

$event->sendMessage(array(1111111111), "My Message");

?>
```

5. Dealing with extra parameters in sendMessage
--------------------------------------

For usability purposes the `sendMessage` call focuses on the recipients and the content. In order to specify and of the additional parameters defined
in the [Clickatell document](http://www.clickatell.com), you can use the `extra` parameter and pass them as an array.


6. Callbacks
---------------

You can listen to clickatell callbacks by using the `Callback::parseCallback();` function. It's a helper function
to make sure the required parameters are including in the `$_GET` array.

Parameters: apiMsgId, cliMsgId, to, timestamp, from, status, charge

Example

``` php
use Clickatell\Callback;

Callback::parseCallback(function ($values) {
    // var_dump($values);
    // Contains: apiMsgId, cliMsgId, to, timestamp, from, status, charge
});

?>
```
