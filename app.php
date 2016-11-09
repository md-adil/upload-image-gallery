<?php
require('vendor/autoload.php');

$args = $argv;
array_shift($args);
(new Dotenv\Dotenv($_SERVER['USERPROFILE'], '.upload-gallery'))->load();

$upload_dir = 'uploaded_files';
$link_file = 'uploaded_links.txt';


$client = new GuzzleHttp\Client();

// die(print_r(openssl_get_cert_locations(),1));

if(!is_dir($upload_dir)) {
	mkdir($upload_dir);
}

foreach(glob('*.*') as $file) {
	if($file == $link_file) continue;
	echo 'uploading: ' . $file . "\n";
	// $file = realpath($file);
	// die();
	// $res = $client->api('image')->upload([
	// 	'image' => file_get_contents($file),
 //    	'type'  => 'base64',
	// ]);

	uploadImg($file);
	die();
	echo 'Uploaded success' . "\n";
	file_put_contents($link_file, $file . PHP_EOL, FILE_APPEND);
	rename($file, $upload_dir . '/' . $file);
}
function uploadImg($img) {
	$clientId = getenv('CLIENT_ID');
	$client = $GLOBALS['client'];
	$url = 'https://api.imgur.com/3/image.json';
	$req = $client->request('POST', $url, [
		'verify' => false,
	    'headers' => [
	        'Authorization' => 'Client-ID ' . $clientId
	    ],
	    'form_params' => [
	    	'image' => file_get_contents($img)
	    ]
	]);

	print_r($req->getBody()->read(1024));
}