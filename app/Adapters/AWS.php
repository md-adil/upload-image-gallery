<?php
namespace App\Adapters;

use Aws\S3\S3Client;

// Instantiate an Amazon S3 client.

/**
*
*/
class AWS
{
    protected $config;
    public function __construct($config)
    {
        $this->config = $config;
        $this->client = $this->client();
    }

    protected function client()
    {
        return new S3Client($this->config);
    }


    public function store($file, $path, $mime)
    {
        return $this->client->putObject([
            'Bucket' => $this->config['Bucket'],
            'Key' => $path,
            'Body' => $file,
            "ContentType" => $mime
        ]);
    }

    public function delete($filename)
    {
        $this->client()->deleteObject([
            'Bucket' => $this->config['Bucket'],
            'Key' => $file
        ]);
    }

    public function move()
    {
    }
}
