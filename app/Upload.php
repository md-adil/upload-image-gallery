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
	protected $aws;
	protected $pwd;
	function __construct($config)
	{
		$this->aws = new AWS($config);
		$this->pwd = $this->getPwd();
	}

	protected function getPwd() {
		$pwd = getcwd();
		$pwd = explode(DIRECTORY_SEPARATOR, $pwd);
		$pwd = end($pwd);
		return $pwd;
	}

	public function println() {
		echo implode(' ', func_get_args()) . PHP_EOL;
	}

	public function upload($files = []) {
		$this->createUploadsDir();
		file_put_contents($this->upload_links, $this->pwd . "\t", FILE_APPEND);
		if(!$files) {
			$files = $this->findFiles();
		}


		$this->println("Total files to upload: ", count($files));

		foreach ($files as $file) {
			$this->println("Uploading: ", $file);
			try {
				$upload_link = $this->uploadToAWS($file);
				$upload_link = $upload_link['ObjectURL'];
				$this->storeLink($upload_link, $file);
				$this->println("Uploaded: ", $upload_link);
				$this->moveFile($file);
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
		$info = pathinfo($file);
		$ext = $info['extension'];
		$filename = $info['filename'];
		$company = explode('_', $filename);
		$company = $company[0];
		$company = $this->str_slug($company);
		$location = sprintf('%s/%s/%s.%s',
			$company,
			date('Y/m'),
			uniqid(),
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
		file_put_contents($this->upload_links, $link . "\t", FILE_APPEND);
		file_put_contents($this->upload_links_map, $link . ',' . $file . PHP_EOL, FILE_APPEND);
	}

	public function str_slug($name) {
		$name = preg_replace('~[^\pL\d]+~u', '-', $name);
		return strtolower($name);
	}

}