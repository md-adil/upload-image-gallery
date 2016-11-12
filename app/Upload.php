<?php
namespace App;

use App\Adapters\AWS;

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
	protected $uploadUrl = 'https://api.imgur.com/3/image.json';
	protected $aws;
	function __construct($client, $clientId = null, $clientSecret = null)
	{

		$this->client = $client;
		$this->clientId = $clientId ?: getenv('CLIENT_ID');
		$this->clientSecret = $clientSecret ?: getenv('CLIENT_ID');
		$this->aws = new AWS([
			'Key' => 'key',
			'Secret' => 'secret',
			'Bucket' => 'Bucket'
		]) 
	}

	public function println() {
		echo implode(' ', func_get_args()) . PHP_EOL;
	}

	public function upload($files = []) {
		$this->createUploadsDir();
		if(!$files) {
			$files = $this->findFiles();
		}

		$this->println("Total files to upload: ", count($files));

		foreach ($files as $file) {
			$this->println("Uploading: ", $file);
			try {
				$upload = $this->uploadToImgur($file);
				if($upload && $upload->status == 200) {
					$this->storeLink($upload->data->link, $file);
					$this->println("Uploaded: ", $upload->data->link);
					$this->moveFile($file);
				} else {
					$this->println("Something went wrong.");
				}
			} catch (\Exception $e) {
				$this->println("Error: ", $e->getMessage());
			}
		}
	}

	public function uploadToImgur($file) {
		$res = $this->client->request('POST', $this->uploadUrl, [
			'verify' => false,
		    'headers' => [
		        'Authorization' => 'Client-ID ' . $this->clientId
		    ],
		    'form_params' => [
		    	'image' => base64_encode(file_get_contents($file))
		    ]
		]);

		if($res) {
			return json_decode($res->getBody()->read(1024));
		}
	}

	public function uploadToAWS($file) {
		$company = '';
		$ext = '';
		$filename = '';
		$location = sprintf('%s/%s/%s.%s',
			$company,
			date('Y/m'),
			$filename,
			$ext
		);
		
		return $this->aws->store($file, $location);
	}


	public function findFiles() {
		$files = glob('*.*');
		if(!$files) return [];
		return array_diff($files, $this->ignore_files);
	}

	public function createUploadsDir() {
		if(!is_dir($this->upload_dir)) {
			mkdir($this->upload_dir);
		}
	}

	public function moveFile($file) {
		rename($file, $this->upload_dir . '/' . $file);
	}

	public function storeLink($link, $file) {
		file_put_contents($this->upload_links, $link . PHP_EOL, FILE_APPEND);
		file_put_contents($this->upload_links_map, $link . ',' . $file . PHP_EOL, FILE_APPEND);
	}

}