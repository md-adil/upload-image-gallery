<?php
require('vendor/autoload.php');
use App\Upload;

$args = $argv;
array_shift($args);

(new Dotenv\Dotenv($_SERVER['USERPROFILE'], '.upload-gallery'))->load();

$client = new GuzzleHttp\Client(['verify'=>false]);

$uploader = new Upload($client);
$uploader->upload($args);
// print_r($uploader->findFiles());