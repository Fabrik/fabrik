<?php

namespace FusionExport;

class Exporter
{
    private $exportDoneListener;

    private $exportStateChangedListener;

    private $exportConfig;

    private $exportServerHost;

    private $exportServerPort;

    private $client;

    public function __construct(ExportConfig $exportConfig)
    {
        $this->exportConfig = $exportConfig;
        
    }

    public function setExportConnectionConfig($exportServerHost, $exportServerPort)
    {
        $this->exportServerHost = $exportServerHost;
        $this->exportServerPort = $exportServerPort;
    }

    public function start($outputDir, $unzip)
    {
        $this->client = new \GuzzleHttp\Client();
        
        $configData = $this->getFormattedExportConfigs();
        $url = $this->exportServerHost . ':' . $this->exportServerPort . "/api/v2.0/export";
        $multipartArray = $this->createMultipartData($configData);
        
        $response = $this->client->request('POST', $url, [
            'multipart' => $multipartArray
        ]);

        if (isset($configData['payload'])) {
            unlink($configData['payload']);
        }

        return $this->saveResponse($response->getBody()->getContents(), $outputDir, $unzip);
    }
    
    private function createMultipartData($configData)
    {
        $multipart = array();
        if(count($configData)>0){
            foreach($configData as $key => $value){
                $arr = array();
                $arr['name'] = $key;
                if(strcasecmp($key,'payload') == 0){
                    $arr['contents'] = fopen($value,'r');
                }else{
                    $arr['contents'] = $value;
                }
                array_push($multipart,$arr);
            }
        }
        return $multipart;
    }
    
    private function saveResponse($contents, $outputDir, $unzip)
    {
        $exportedFiles = [];

        $fileName = $outputDir . DIRECTORY_SEPARATOR . "fusioncharts_export.zip";
        file_put_contents($fileName, $contents);
        $exportedFiles[] = realpath($fileName);

        if (!$unzip) return $exportedFiles;

        $zipFile = new \ZipArchive();
        $exportedFiles = [];

        if (!$zipFile->open($fileName)) {
            throw new \Exception('Failed to open exported archive file');
        }
        
        $zipFile->extractTo($outputDir);

        for ($i = 0; $i < $zipFile->numFiles; $i++) {
            $path = realpath($outputDir . DIRECTORY_SEPARATOR . $zipFile->getNameIndex($i));
            $exportedFiles[] = $path;
        }
        
        $zipFile->close();
        unlink($fileName);

        return $exportedFiles;
    }

    private function checkExportError($exportResult)
    {
        $exportResult = json_decode($exportResult);

        if (array_key_exists('error', $exportResult)) {
            return $exportResult->error;
        }
    }

    private function getFormattedExportConfigs()
    {
        return $this->exportConfig->getFormattedConfigs();
    }

    private function startsWith($haystack, $needle)
    {
        if (strpos($haystack, $needle) === 0) {
            return true;
        }

        return false;
    }
}
