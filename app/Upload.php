<?php
namespace App;

use App\Adapters\AWS;
use Intervention\Image\Exception\NotReadableException;

/**
*
*/
class Upload
{
    protected $client;
    protected $upload_dir = 'uploaded_files';
    protected $upload_links = 'uploaded_links.txt';
    protected $upload_links_map = 'uploaded_links.csv';
    protected $ignore_files = [
        'uploaded_files', 'uploaded_links.txt', 'uploaded_links.csv'
    ];

    protected $clientId;
    protected $aws;
    protected $pwd;
    public function __construct($config)
    {
        $this->aws = new AWS($config);
        $this->pwd = $this->getPwd();
    }

    protected function getPwd()
    {
        $pwd = getcwd();
        $pwd = explode(DIRECTORY_SEPARATOR, $pwd);
        $pwd = end($pwd);
        return $pwd;
    }

    public function println()
    {
        echo implode(' ', func_get_args()) . PHP_EOL;
    }

    public function upload($files = [])
    {
        $this->createUploadsDir();
        file_put_contents($this->upload_links, $this->pwd . "\t", FILE_APPEND);
        if (!$files) {
            $files = $this->findFiles();
            // dd($files);
        }


        $this->println("Total files to upload: ", count($files));

        foreach ($files as $file) {
            $this->println("Uploading: ", $file);
            try {
                $upload_link = $this->uploadToAWS($file);
                $this->storeLink($upload_link, $file);
                $this->println("Uploaded: ", $upload_link);
                $this->moveFile($file);
            } catch (NotReadableException $e) {
                $this->println('Unsupported: ' . $file);
            } catch (\Exception $e) {
                $this->println("Error: ", $e->getMessage());
            }
        }
    }

    public function uploadToImgur($file)
    {
        $res = $this->client->request('POST', $this->uploadUrl, [
            'verify' => false,
            'headers' => [
                'Authorization' => 'Client-ID ' . $this->clientId
            ],
            'form_params' => [
                'image' => base64_encode(file_get_contents($file))
            ]
        ]);

        if ($res) {
            return json_decode($res->getBody()->read(1024));
        }
    }

    public function uploadToAWS($file)
    {
        $image = new Image($file);
        $name = $this->random_str();
        $ext = $image->getExt();
        $mime = $image->getMime();
        $largeImage = $this->getLocation($ext, 'large', $name);
        $this->aws->store((string)$image->getLarge(), $largeImage, $mime);
        $this->aws->store((string)$image->getMedium(), $this->getLocation($ext, 'medium', $name), $mime);
        $this->aws->store((string)$image->getSmall(), $this->getLocation($ext, 'small', $name), $mime);
        $this->aws->store((string)$image->getThumb(), $this->getLocation($ext, 'thumb', $name), $mime);
        return 'http://s.bigly.io/' . $largeImage;
        return $this->aws->store($file, $location, $mime);
    }

    protected function getLocation($ext, $size, $name)
    {
        return sprintf(
            'products/%s/%s/%s.%s',
            $size,
            date('Y/m'),
            $name,
            $ext
        );
    }

    public function random_str($length = 30)
    {
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyz-';
        $pieces = [];
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $pieces []= $keyspace[random_int(0, $max)];
        }
        return implode('', $pieces);
    }

    public function findFiles()
    {
        $files = glob('*.*', GLOB_BRACE);
        if (!$files) {
            return [];
        }
        return array_diff($files, $this->ignore_files);
    }

    public function createUploadsDir()
    {
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir);
        }
    }

    public function moveFile($file)
    {
        rename($file, $this->upload_dir . '/' . $file);
    }

    public function storeLink($link, $file)
    {
        file_put_contents($this->upload_links, $link . "\t", FILE_APPEND);
        file_put_contents($this->upload_links_map, $link . ',' . $file . PHP_EOL, FILE_APPEND);
    }

    public function str_slug($name)
    {
        $name = preg_replace('~[^\pL\d]+~u', '-', $name);
        return strtolower($name);
    }
}
