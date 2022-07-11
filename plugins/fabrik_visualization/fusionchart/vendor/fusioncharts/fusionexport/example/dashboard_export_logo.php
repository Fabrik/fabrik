<?php

// Adding a logo or heading to the dashboard

require __DIR__ . '/../vendor/autoload.php';

// Use the sdk
use FusionExport\ExportManager;
use FusionExport\ExportConfig;

// Instantiate the ExportConfig class and add the required configurations
$exportConfig = new ExportConfig();
$exportConfig->set('chartConfig', realpath(__DIR__ . '/resources/multiple.json'));
$exportConfig->set('templateFilePath', realpath(__DIR__ . '/resources/template.html'));
$exportConfig->set('dashboardLogo', realpath(__DIR__ . '/resources/logo.jpg'));
$exportConfig->set('dashboardHeading', 'FusionCharts');
$exportConfig->set('dashboardSubheading', 'The best charting library in the world');

// Instantiate the ExportManager class
$exportManager = new ExportManager();
// Call the export() method with the export config
$files = $exportManager->export($exportConfig, '.', true);

foreach ($files as $file) {
    echo $file . "\n";
}
