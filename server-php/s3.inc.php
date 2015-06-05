<?php
require_once '/server/path/to/aws/aws-autoloader.php';
use Aws\Common\Aws;



class S3Upload {
	private $allowOverride = false; // VERY bad idea to enable this if you use CloudFront to publish your S3 files; but fine if you ONLY use S3
	private $client;
	private $bucket;
	private $folder;
	private $cloudfront;

	private $file_keys = array();
	private $dir_keys = array();

	private $configPath = '/aws/server-php/config/'; // relative to DOCUMENT_ROOT

	private function error($msg) {
		header('HTTP/1.1 400 Bad Request');
		header("Content-Type: application/json; charset=utf-8");
		echo '{"error":"'.$msg.'"}';
		die();
	}

	public function __construct($bucket, $credentialsFile, $folder='') {
		$this->configPath = $_SERVER['DOCUMENT_ROOT'] . $this->configPath;

		$configFile = $this->configPath .$credentialsFile;
		if(!file_exists($configFile)) {
			return $this->error("credentials not found");
		}
		
		$this->bucket = $bucket;

		if($folder && substr($folder, -1) != '/') {
			// folder may be an empty string, but if given, it shuold end on a / or we are NOT checking on a folder but instead on a file prefix (which may also include a path). in this case we only look for folders, therefore:
			$folder .= '/';
		} elseif($folder && substr($folder, -2) == '//') {
			// folder may be an empty string, but if given, it shuold end on a / or we are NOT checking on a folder but instead on a file prefix (which may also include a path). in this case we only look for folders, therefore:
			return $this->error("video id missing");
		}
		$this->folder = $folder;
		$this->cloudfront = $CLOUDFRONT;
		
		$this->client = Aws::factory( $configFile )->get('S3');

	}
	private function getFilesInFolder($recursive = false) {
		$folder = $this->folder;

		$listConfig = array(
			'Bucket'    => $this->bucket,
			// 'MaxKeys'	=> 1000, // 1000 is the max of this function! else I need to use iterateItem()
			'Prefix'    => $folder // used to filter by sub-folder (or file-prefix), leave empty for root dir
			,'Delimiter' => '' // if given the output will not include files in subfolders
		);
		if(!$recursive) {
			$listConfig['Delimiter'] = '/'; // if given the output will not include files in subfolders
		}
		try {
			$objects = $this->client->listObjects($listConfig);
		} catch (S3Exception $e) {
			echo $e->getMessage() . "\n";
		}

		# list all folders, only available if $recursive===false
		if($objects["CommonPrefixes"]) {
			foreach($objects["CommonPrefixes"] as $dir) {
				$this->dir_keys[] = $dir["Prefix"];
			}
		}
		# list all files in $this->folder
		if($objects["Contents"]) {
			$length = strlen($folder);
			foreach($objects["Contents"] as $file) {
				$key = substr($file["Key"],$length);
				// README empty dirs will show a single Contents entry with the path of $folder as Key, resulting in an empty entry in file_keys if we dont catch it here
				if($key) {
					$this->file_keys[] = $key; # cut off the path and only return file-names
				}
			}
		}
	}
	public function getSignedUrl($filename, $mime) {
		if(!$filename) {
			return $this->error('filename missing');
		}
		if(!$mime) {
			return $this->error('mime-type missing');
		}
		$final_filename = $this->get_file_name($filename);
		try {
			$signedUrl = $this->client->getCommand('PutObject', array(
				'Bucket' => $this->bucket,
				'Key' => $this->folder . $final_filename,
				'ContentType' => $mime,
				'Body'        => '',
				'ContentMD5'  => false
			))->createPresignedUrl('+30 minutes');
		} catch (S3Exception $e) {
			echo $e->getMessage() . "\n";
		}

		$signedUrl .= '&Content-Type='.urlencode($mime);
		return array('url'=>$signedUrl);
	}



////////////////////////////////// FILE NAME
	private function upcount_name_callback($matches) {
		$index = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
		$ext = isset($matches[2]) ? $matches[2] : '';
		return '_'.$index.''.$ext;
	}
	private function upcount_name($name) {
		return preg_replace_callback(
			'/(?:(?:_([\d]+))?(\.[^.]+))?$/',
			array($this, 'upcount_name_callback'),
			$name,
			1
		);
	}
	private function get_unique_filename($name){
		while( in_array($name, $this->dir_keys) ) {
			$name = $this->upcount_name($name);
		}
		while( in_array($name, $this->file_keys) ) {
			$name = $this->upcount_name($name);
		}
		return $name;
	}
	private function removeUrlspecialchars($filename) {
		return preg_replace('/[^_a-z0-9.]+/i', '-', $filename);
	}
	private function trim_file_name($name) {
		// if the filename has an extension, make sure it's lower-case
		if(strpos($name, '.')!==false) {
			$temp = explode( '.', $name);
			$temp[] = strtolower(array_pop( $temp ));
			$name = implode('.', $temp);
		}

		// Remove path information and dots around the filename, to prevent uploading
		// into different directories or replacing hidden system files.
		// Also remove control characters and spaces (\x00..\x20) around the filename:
		$name = $this->removeUrlspecialchars(trim(basename(stripslashes($name)), ".\x00..\x20"));
		return $name;
	}
	/////
	// $name = original filename; $type = mime-type / content-type
	/////
	private function get_file_name($name){
		if($this->allowOverride) {
			return $this->trim_file_name($name);
		} else {
			$this->getFilesInFolder();
			return $this->get_unique_filename( $this->trim_file_name($name) );
		}
	}
}