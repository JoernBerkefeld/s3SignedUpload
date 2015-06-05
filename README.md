# s3SignedUpload
required scripts to add s3 upload to your web-app via presigned URLs

####Important####
Make sure you change the CORS settings of you bucket with the XML file here. You may change AllowedOrigin from "*" to your own domain, but make sure to include the protocol scheme (http:// or https://).
HEAD, DELETE and POST are not actually used by the script, but this is how you tell AWS to accept the OPTIONS header which in fact IS used right before starting the upload via PUT.

Why PUT instead of POST? Well, because via PHP you can only presign putObject operations. There is no postObject in AWS's PHP SDK.