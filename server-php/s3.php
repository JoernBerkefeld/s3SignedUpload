<?php

##########
###
#	-
#	- ADD YOUR OWN USER ACCOUNT CHECKING HERE TO VALIDATE THE UPLOAD! 
#	- 
###
##########
$S3_BUCKET = 'my-s3-bucket';
$S3_credentialsFile = 'demo.inc.php'; // in config-folder (folder can be changed in s3.inc.php)
$prefix = "my/prefix/"; // leave empty for bucket-root




$filename = isset($_GET['file']) ? $_GET['file'] : '';
$mime = isset($_GET['mime']) ? $_GET['mime'] : '';


require_once 's3.inc.php';


# create client
$s3Upload = new S3Upload($S3_BUCKET, $S3_credentialsFile, $prefix);

$signedUrl = $s3Upload->getSignedUrl($filename, $mime);

header("Content-Type: application/json; charset=utf-8");
echo json_encode($signedUrl);