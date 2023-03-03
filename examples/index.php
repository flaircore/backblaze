<?php
// Autoload dependencies installed via composer .
require_once __DIR__ . '/vendor/autoload.php';

use Flaircore\Backblaze\Clients\BackblazeClient;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// B2 credentials
define('B2_REGION', 'us-west-004');
define('B2_API_KEY', '0045932faddc0ca0000000003');
define('B2_API_SECRET', 'K004lzpkfwrTEY1QIw74yMu54IN2yuA');
define('B2_BUCKET_NAME', 'trolls-dev-pub');
define('B2_BUCKET_ID', 'd5f9a3225fca4ded8c600c1a');
define('B2_ENDPOINT', 'https://s3.us-west-004.backblazeb2.com');

$b2_configs = [
    'B2_REGION' => B2_REGION,
    'B2_API_KEY' => B2_API_KEY,
    'B2_API_SECRET' => B2_API_SECRET,
    'B2_BUCKET_NAME' => B2_BUCKET_NAME,
    'B2_BUCKET_ID' => B2_BUCKET_ID,
    'B2_ENDPOINT' => B2_ENDPOINT
];

$logs_dir = __DIR__ . '/dev_logs/';
// Log channel (local_dev), to track all logs/errors.
$log = new Logger('local_dev');
$log->pushHandler(new StreamHandler($logs_dir .'dev.log', Level::Warning));

// Global error handler for unhandled exceptions.
set_exception_handler(function (\Throwable $e) use ($log){
    $log->error($e->getMessage());
    echo $e;
});

$backBlaze = new \Flaircore\Backblaze\Backblaze($b2_configs);
//$backBlaze->uploadALargeFile();
//$backBlaze->uploadASmallFile();


?>

<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport"
	      content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>B2 Cloud storage examples</title>
</head>
<body>
<h1> Just an example, remember to "require_once __DIR__ . '/vendor/autoload.php';" </h1>
</body>
</html>
