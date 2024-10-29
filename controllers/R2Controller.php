<?php

require 'vendor/autoload.php';
use core\route;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class S3Uploader
{
    private $s3Client;
    private $bucketName;
    private $directory;

    public function __construct($credentials, $bucketName, $directory)
    {
        $this->s3Client = new S3Client([
            'version'     => 'latest',
            'credentials' => $credentials,
            'region'      => $credentials['region'],
            'endpoint'    => 'https://eb9ed43df20b40144fb9ce2508107577.r2.cloudflarestorage.com/', 
        ]);

        $this->bucketName = $bucketName;
        $this->directory = $directory;
    }

    public function pushtoaws()
    {
        $files = scandir($this->directory);
        $files = array_diff($files, ['.', '..']);

        try {
            foreach ($files as $file) {
                $filePath = $this->directory . '/' . $file;

                $result = $this->s3Client->putObject([
                    'Bucket' => $this->bucketName,
                    'Key'    => $file,
                    'Body'   => fopen($filePath, 'r'),
                ]);

                echo "Uploaded $file to {$this->bucketName}" . PHP_EOL;
            }
        } catch (AwsException $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }
}




