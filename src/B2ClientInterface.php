<?php

namespace Flaircore\Backblaze;
interface B2ClientInterface {
	public function __construct(array $b2_configs);
	public function b2AuthorizeAccount();
	public function b2StartLargeFile(string $api_url, string $account_auth_token, string $key);
	public function b2UploadChunks(string $local_file, string $auth_token, string $api_url, string $file_id);
	public function b2ListUnfinishedLargeFiles(string $api_url, string $account_auth_token);
}
