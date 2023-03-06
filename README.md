# PHP wrapper around B2 Cloud storage API endpoints.


# Examples:

Remember to autoload your composer dependencies if not already loaded:
```php
// Autoload dependencies installed via composer .
require_once __DIR__ . '/vendor/autoload.php';
```

### Setup:

* BackblazeClient takes an array with your bucket configuration details.
* 
```php

$b2_configs = [
    'B2_REGION' => B2_REGION,
    'B2_API_KEY' => B2_API_KEY,
    'B2_API_SECRET' => B2_API_SECRET,
    'B2_BUCKET_NAME' => B2_BUCKET_NAME,
    'B2_BUCKET_ID' => B2_BUCKET_ID,
    'B2_ENDPOINT' => B2_ENDPOINT
];


$this->b2Client = new BackblazeClient($b2_configs);

```

* Uploading small files.
```php
// Steps

// Authorize requests

// Get the upload url + the authorizationToken

// Upload the file (b2_upload_file)

$b2_auth = $this->b2Client->b2AuthorizeAccount();

// Required from b2_auth

$auth_token = $b2_auth->authorizationToken;

$api_url = $b2_auth->apiUrl;


$file_name = '51863901_1591128994364639_5533062884642328214_n.jpg';

$key = 'a-file-folder/'. $file_name;
// Assuming your files are stored in the '/uploads/', change to fit your folders.

$local_file = dirname(__DIR__, 1) . '/uploads/'. $file_name;


$upload_details = $this->b2Client->b2GetUploadUrl($api_url, $auth_token);

$upload_file = $this->b2Client->b2UploadFile($key, $local_file, $upload_details->uploadUrl, $upload_details->authorizationToken);

var_dump($upload_details);

var_dump($upload_file);

die('HERE TOO');
```


* Uploading large files (multipart).
```php
// Steps
//
//
//
$b2_auth = $this->b2Client->b2AuthorizeAccount();
// Required from b2_auth
$auth_token = $b2_auth->authorizationToken;
$api_url = $b2_auth->apiUrl;

$file_name = 'BULLET TRAIN - Official Trailer (HD).mp4';
$key = 'a-folder/'. $file_name;
// Assuming your files are stored in the '/uploads/', change to fit your folders.
$local_file = dirname(__DIR__, 1) . '/uploads/'. $file_name;

$start_upload_file = $this->b2Client->b2StartLargeFile($api_url, $auth_token, $key);
$fileId = $start_upload_file->fileId// From here, you can save the values required in b2UploadChunks($local_file, $auth_token, $api_url, $fileId);
// below, and later fire that action via a cron job, to make content editing easier (take less time) maybe.

// upload chunks
$large_upload_res = $this->b2Client->b2UploadChunks($local_file, $auth_token, $api_url, $fileId);
// Delete local file maybe
// unlink( $local_file );
var_dump($large_upload_res);
die('DONE for a large file');

```