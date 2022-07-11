<?php

namespace FusionExport;

use FusionExport\Converters\NumberConverter;
use FusionExport\Converters\BooleanConverter;
use PHPHtmlParser\Dom;

class ResourcePathInfo 
{
    public $internalPath;
    public $externalPath;
}

class ExportConfig
{
    protected $configs;
    
    public function __construct()
    {
        $this->typingsFile = __DIR__ . '/../config/fusionexport-typings.json';
        $this->metaFile = __DIR__ . '/../config/fusionexport-meta.json';
        $this->configs = [];
        $this->formattedConfigs = [];

        $this->readTypingsConfig();
        $this->readMetaConfig();
        $this->collectedResources = array();
    }

    public function set($name, $value)
    {
        $this->configs[$name] = $value;

        $this->sanitizeConfig($name);

        return $this;
    }

    public function get($name)
    {
        return $this->configs[$name];
    }

    public function remove($name)
    {
        unset($this->configs[$name]);
        return $this;
    }

    public function has($name)
    {
        return array_key_exists($name, $this->configs);
    }

    public function clear()
    {
        $this->configs = [];
    }

    public function count()
    {
        return count($this->configs);
    }

    public function configNames()
    {
        return array_keys($this->configs);
    }

    public function configValues()
    {
        return array_values($this->configs);
    }

    public function cloneConfig()
    {
        $newExportConfig = new ExportConfig();

        foreach ($this->configs as $key => $value) {
            $newExportConfig->set($key, $value);
        }

        return $newExportConfig;
    }

    public function getFormattedConfigs()
    {    
        $this->formatConfigs();
        return $this->formattedConfigs;
    }

    private function sanitizeConfig($name)
    {
        $value = $this->configs[$name];

        if (!property_exists($this->typings, $name)) {
            throw new \Exception($name . ' is not a valid config.');
        }

        $type = $this->typings->$name->type;

        if (property_exists($this->typings->$name, 'converter')) {
            $converter = $this->typings->$name->converter;

            if ($converter === 'BooleanConverter') {
                $value = BooleanConverter::convert($value);
            } else if ($converter === 'NumberConverter') {
                $value = NumberConverter::convert($value);
            }
        }

        if (gettype($value) !== $type) {
            throw new \Exception($name . ' must be a ' . $type . '.');
        }

        $this->configs[$name] = $value;
    }
    
    private function endswith($string, $test) 
    {
        $strlen = strlen($string);
        $testlen = strlen($test);
        if ($testlen > $strlen) return false;
        return substr_compare($string, $test, $strlen - $testlen, $testlen) === 0;
    }
    
    private function formatConfigs()
    {
        $zipBag = array();
        foreach($this->configs as $key=> $value)
        {
            switch($key)
            {
                case "chartConfig":
                    if($this->endswith($this->configs['chartConfig'], '.json'))
                    {
                        $this->formattedConfigs['chartConfig'] = file_get_contents($this->configs['chartConfig']);
                    }
                    else{
                        $this->formattedConfigs['chartConfig'] = $this->configs['chartConfig'];
                    }
                    break;
                case "inputSVG":
                    $obj = new ResourcePathInfo;
                    $internalFilePath = "inputSVG.svg";
                    $obj->internalPath = $internalFilePath;
                    $obj->externalPath = $this->configs['inputSVG'];
                    $this->formattedConfigs['inputSVG'] = $internalFilePath;
                    array_push($zipBag,$obj);
                    break;
                case "callbackFilePath":
                    $obj = new ResourcePathInfo;
                    $internalFilePath = "callbackFile.js";
                    $this->formattedConfigs['callbackFilePath'] = $internalFilePath;
                    $obj->internalPath = $internalFilePath;
                    $obj->externalPath = $this->configs['callbackFilePath'];
                    array_push($zipBag,$obj);
                    break;
                case "dashboardLogo":
                    $obj = new ResourcePathInfo;
                    $internalFilePath = "logo." . pathinfo($this->configs['dashboardLogo'], PATHINFO_EXTENSION);
                    $obj->internalPath = $internalFilePath;
                    $obj->externalPath = $this->configs['dashboardLogo'];
                    $this->formattedConfigs['dashboardLogo'] = $internalFilePath;
                    array_push($zipBag,$obj);
                    break;
                case "templateFilePath":                    
                    $templatePathWithinZip = '';
                    $zipPaths = array();
                    $this->createTemplateZipPaths($zipPaths,$templatePathWithinZip);
                    $this->formattedConfigs['templateFilePath'] = $templatePathWithinZip;
                    foreach($zipPaths as $path)
                    {
                        array_push($zipBag,$path);
                    }
                    break;
                case "outputFileDefinition":
                    $this->formattedConfigs['outputFileDefinition'] = file_get_contents($this->configs['outputFileDefinition']);
                    break;
                case "asyncCapture":
                    if(empty($this->configs['asyncCapture']) < 1){
                        if(strtolower($this->configs['asyncCapture']) == "true"){
                            $this->formattedConfigs['asyncCapture'] = "true";
                        }
                        else{
                            $this->formattedConfigs['asyncCapture'] = "false";
                        }
                    }
                    break;
                default: 
                    $this->formattedConfigs[$key] = $this->configs[$key];
            }
        }
        if(count($zipBag)> 0){
            $zipFile = $this->generateZip($zipBag);
            $this->formattedConfigs['payload'] = $zipFile;
        }
        $this->formattedConfigs['clientName'] = 'PHP';
        
        $this->formattedConfigs['platform'] = PHP_OS;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->formattedConfigs['platform'] = 'win32';
        } 
    }
    
    private function findResources()
    {
        $dom = new Dom();
        $dom->setOptions([ 
            'removeScripts' => false,
        ]);
        $dom->load(file_get_contents($this->configs['templateFilePath']));

        $links = $dom->find('link')->toArray();
        $scripts = $dom->find('script')->toArray();
        $imgs = $dom->find('img')->toArray();

        $links = array_map(function ($link) {
            return $link->getAttribute('href');
        }, $links);

        $scripts = array_map(function ($script) {
            return $script->getAttribute('src');
        }, $scripts);

        $imgs = array_map(function ($img) {
            return $img->getAttribute('src');
        }, $imgs);

        $this->collectedResources = array_merge($links, $scripts, $imgs);
        $this->collectedResources = Helpers::resolvePaths(
            $this->collectedResources, 
            dirname(realpath($this->configs['templateFilePath']))
        );

        $this->collectedResources = array_unique($this->collectedResources);
        
        $this->removeRemoteResources();
        return $this->collectedResources;
    }
    
    private function removeRemoteResources() 
    {
        $this->collectedResources = array_filter(
            $this->collectedResources, 
            function ($res) {
                if (Helpers::startsWith($res, 'http://')) return false;

                if (Helpers::startsWith($res, 'https://')) return false;

                if (Helpers::startsWith($res, 'file://')) return false;

                return true;
            }
        );
    }

    private function createTemplateZipPaths(&$outZipPaths, &$outTemplatePathWithinZip)
    {
        $templatePathWithinZip ='';
        $listExtractedPaths = array();
        $listExtractedPaths = $this->findResources();
        $listResourcePaths = array();
        $baseDirectoryPath = null;
        if(isset($this->configs['resourceFilePath'])){
            Helpers::globResolve($listResourcePaths, $baseDirectoryPath,$this->configs[resourceFilePath]);
        }
        $templateFilePath = realpath($this->configs['templateFilePath']);
        if (!isset($baseDirectoryPath)) {
            array_push($listExtractedPaths,$templateFilePath);
            $commonDirectoryPath = Helpers::findCommonPath($listExtractedPaths);
            if(isset($commonDirectoryPath)){
                $baseDirectoryPath = $commonDirectoryPath;
            }
            if(strlen($baseDirectoryPath) == 0){
                $baseDirectoryPath = dirname($templateFilePath);
            }
            
        }
        $mapExtractedPathAbsToRel = array();
        foreach($listExtractedPaths as $tmpPath){
            $mapExtractedPathAbsToRel[$tmpPath] = $this->getRelativePath($tmpPath,$baseDirectoryPath);
        }
        foreach($listResourcePaths as $tmpPath){
            $mapExtractedPathAbsToRel[$tmpPath] = $this->getRelativePath($tmpPath,$baseDirectoryPath);
        }
        $templateFilePathWithinZipRel = $this->getRelativePath($templateFilePath,$baseDirectoryPath);
        $mapExtractedPathAbsToRel[$templateFilePath] = $templateFilePathWithinZipRel;
        $zipPaths = array();
        $zipPaths = $this->generatePathForZip($mapExtractedPathAbsToRel,$baseDirectoryPath);
        $templatePathWithinZip = $templatePathWithinZip . DIRECTORY_SEPARATOR . $templateFilePathWithinZipRel;
        $outZipPaths = $zipPaths;
        $outTemplatePathWithinZip = $templatePathWithinZip;
    }
    
    private function generatePathForZip($listAllFilePaths, $baseDirectoryPath)
    {
        $listFilePath = array();
        foreach($listAllFilePaths as $key => $value){
            $obj = new ResourcePathInfo;
            $obj->internalPath = $value;
            $obj->externalPath = $key;
            array_push($listFilePath,$obj);
        }
        return $listFilePath;
    }
    
    private function getRelativePath($from, $to)
    {
        $internalPath = ltrim(trim(str_replace($to,'',$from),DIRECTORY_SEPARATOR));
        return trim($internalPath);
    }
    
    private function generateZip($fileBag)
    {
        
        $zipFile = new \ZipArchive();
        $realPath = realpath(sys_get_temp_dir());
        $fileName = $realPath. DIRECTORY_SEPARATOR ."fcexport.zip";
        $zipFile->open($fileName, \ZipArchive::CREATE);
        foreach ($fileBag as $files) {
            if(strlen((string)$files->internalPath) > 0 && strlen((string)$files->externalPath) > 0){
                $zipFile->addFile($files->externalPath, $files->internalPath);
            }
            
        }
        $zipFile->close();
        return $fileName;
    }
    
    private function readTypingsConfig()
    {
        $this->typings = json_decode(file_get_contents($this->typingsFile));
    }

    private function readMetaConfig()
    {
        $this->meta = json_decode(file_get_contents($this->metaFile));
    }
}
