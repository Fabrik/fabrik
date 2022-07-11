<?php

namespace FusionExport;

use \Aws\S3\S3Client;
use \Aws\S3\Exception\S3Exception;
use \Aws\Credentials\Credentials;

class ExportManager
{
    private $host;
    
    private $port;

    public function __construct(
        $host = Constants::DEFAULT_HOST,
        $port = Constants::DEFAULT_PORT
    )
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function export(ExportConfig $exportConfig, $outputDir = '.', $unzip = false)
    {
        $exporter = new Exporter($exportConfig);

        $exporter->setExportConnectionConfig($this->host, $this->port);

        return $exporter->start($outputDir, $unzip);
    }

    public static function path_join(...$paths) 
    {
        $saPaths = [];

        foreach ($paths as $path) {
            $saPaths[] = trim($path, DIRECTORY_SEPARATOR);
        }

        $saPaths = array_filter($saPaths);

        return join(DIRECTORY_SEPARATOR, $saPaths);
    }

    public static function isDirectory($conn_id, $dir) 
    { 
        if(@ftp_chdir($conn_id,$dir)) { 
            ftp_cdup($conn_id); 
            return true; 
        } else { 
            return false; 
        } 
    }

    public static function getS3Client($credentials, $region) 
    {
        // Create Amazon Web Service client
        return new S3Client([
            'version'     => 'latest',
            'region'      => $region,
            'credentials' => $credentials,
            'http' => [ 'verify' => false ]    
        ]);
    }

    public static function ftpDirectoryExists($ftpConn, $ftpDirectory) 
    {
        
        $list = ftp_nlist($ftpConn, ".");

        foreach($list as $entry) {
            if($entry != '.' && $entry != '..' && ExportManager::isDirectory($ftpConn, $entry)) {
                if(strtolower($entry) == strtolower($ftpDirectory)){
                    return true;
                }
            }
        }

        return false;
    }

    public static function uploadToAmazonS3($bucketName, $accessId, $secretKey, $export)
    {
        try {
            
            // Get an AS3 client
            $credentials = new Credentials($accessId, $secretKey);
            $s3Client = ExportManager::getS3Client($credentials, 'us-west-2');

            // Flag for ensuring bucket exists in AS3
            $bucketExist = false;
            
            // Iterate the list of buckets to search the desired bucket exist or not.
            $buckets = $s3Client->listBuckets();
            foreach ($buckets['Buckets'] as $bucket){
                if ($bucket['Name'] == $bucketName) {
                    $bucketExist = true;

                    // Find bucket region and get AS3 client again based on region
                    $result = $s3Client->getBucketLocation([
                        'Bucket' => $bucketName
                    ]);
                    $s3Client = ExportManager::getS3Client($credentials, $result['LocationConstraint']);
                    break;
                }
            }

            if ($bucketExist == true) {

                // Loop for creating image file in bucket
                foreach ($export as $file) {

                    // write binary image data in bucket
                    $s3Client->putObject(array(
                        'Bucket' => $bucketName,
                        'Key'    => $file->realName,
                        'Body'   => base64_decode($file->fileContent)
                    ));
                }                                
            } else {
                echo('Failed to upload: due to bucket does not exist.');
            }

        } catch (S3Exception $e) {
            echo('ERROR: ' . $e->getMessage());
        }
    }

    public static function uploadToFTP($ftpHost, $ftpPort, $userName, $loginPassword, $remoteDirectory, $export) 
    {
        try {

            // Connect and login to FTP server
            $ftp_conn = ftp_connect($ftpHost, $ftpPort) or die("Could not connect to $ftpHost");
            $login = ftp_login($ftp_conn, $userName, $loginPassword);

            //Find remote directory, Create directory if not found
            if (!ExportManager::ftpDirectoryExists($ftp_conn, $remoteDirectory)) {
                ftp_mkdir($ftp_conn, $remoteDirectory);
            }

            //Change diretory to remote directory
            ftp_chdir($ftp_conn,$remoteDirectory);

            //Loop for creating image file in remote directory
            foreach ($export as $file) {
                // write binary image data in remote directory
                ftp_put($ftp_conn, $file->realName, 'data://text/plain;base64,' . $file->fileContent, FTP_BINARY);
            }         
                        
            // Close connection
            ftp_close($ftp_conn); 

        } catch (Error $ex) {
            echo('ERROR: ' . $e->getMessage());
        }
    }
}
