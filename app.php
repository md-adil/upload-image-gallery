<?php
require('vendor/autoload.php');
use App\Upload;

function dd() {
	foreach(func_get_args() as $fn) {
		print_r($fn);
	}
	die();
}

$args = $argv;
array_shift($args);

(new Dotenv\Dotenv($_SERVER['USERPROFILE'], '.upload-gallery'))->load();

/*$file = "IMG_4431.JPG";
print_r($file);

die();*/

$uploader = new Upload([
	'Bucket' => getenv('BUCKET'),
	'region' => "ap-south-1",
	'credentials' => [
	    'key'    => getenv('CLIENT_ID'),
	    'secret' => getenv('CLIENT_SECRET')
	],
	'http' => [
		'verify' => false
	],
	'version' => 'latest'
]);

$uploader->upload($args);
// print_r($uploader->findFiles());