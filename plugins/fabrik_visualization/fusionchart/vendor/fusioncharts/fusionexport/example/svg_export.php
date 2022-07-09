<?php

// Converting an SVG image to PNG/JPEG/PDF

require __DIR__ . '/../vendor/autoload.php';

// Use the sdk
use FusionExport\ExportManager;
use FusionExport\ExportConfig;

// Instantiate the ExportConfig class and add the required configurations
$exportConfig = new ExportConfig();
$exportConfig->set('inputSVG', realpath(__DIR__ . '/resources/vector.svg'));

// Instantiate the ExportManager class
$exportManager = new ExportManager();
// Call the export() method with the export config
$files = $exportManager->export($exportConfig, '.', true);

foreach ($files as $file) {
    echo $file . "\n";
}
