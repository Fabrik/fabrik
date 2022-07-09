# FusionExport PHP Client

This is a PHP export client for FusionExport. It communicates with FusionExport through the socket protocol and does the export.

## Installation

To install this package, simply use composer:

```
composer require fusioncharts/fusionexport
```

## Usage

To use the SDK in your project:

```php
use FusionExport\ExportManager;
use FusionExport\ExportConfig;
```

## Getting Started

Let’s start with a simple chart export. For exporting a single chart, save the chartConfig in a JSON file. The config should be inside an array.

```php
<?php

// Exporting a chart

require __DIR__ . '/../vendor/autoload.php';

// Use the sdk
use FusionExport\ExportManager;
use FusionExport\ExportConfig;

// Instantiate the ExportConfig class and add the required configurations
$exportConfig = new ExportConfig();
$exportConfig->set('chartConfig', realpath('resources/single.json'));

// Instantiate the ExportManager class
$exportManager = new ExportManager();
// Call the export() method with the export config
$exportManager->export($exportConfig, '.', true);
```

## API Reference

You can find the full reference [here](https://www.fusioncharts.com/dev/exporting-charts/using-fusionexport/sdk-api-reference/php.html).