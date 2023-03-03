<?php

namespace Flaircore\Backblaze\Clients;

use Flaircore\Backblaze\B2ClientInterface;

class BackblazeClient implements B2ClientInterface {
	use HttpClient;
    private const BASE_URL = 'https://api.backblazeb2.com/b2api/v2/';

    private $b2Configs = [];
    public function __construct(array $b2_configs) {
        $this->b2Configs = $b2_configs;
    }

    /**
     * @param $api_url
     * Provided by b2_authorize_account
     * @param $account_auth_token
     * Provided by b2_authorize_account
     *
     * @return void
     */
    final public function b2ListUnfinishedLargeFiles($api_url, $account_auth_token){
        $bucket_id = $this->b2Configs['B2_BUCKET_ID']; // Provided by b2_create_bucket or b2_list_buckets

        // Construct post info
        $data = array("bucketId" => $bucket_id);
        $post_fields = json_encode($data);
        $session = curl_init($api_url . "/b2api/v2/b2_list_unfinished_large_files");
        curl_setopt($session, CURLOPT_POSTFIELDS, $post_fields);
        // Add headers
        $headers = array();
        $headers[] = "Accept: application/json";
        $headers[] = "Authorization: " . $account_auth_token;
        print_r ($headers);
        curl_setopt($session, CURLOPT_HTTPHEADER, $headers);  // Add headers
        //curl_setopt($session, CURLOPT_HTTPPOST, true);  // HTTP POST
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true); // Receive server response
        $server_output = curl_exec($session);
        curl_close ($session);
        print $server_output;
    }

    /**
     *
     * Auth account
     * https://www.backblaze.com/b2/docs/b2_authorize_account.html
     * @return mixed
     * @throws \Exception
     */
    final public function b2AuthorizeAccount(){
        $application_key_id = B2_API_KEY;
        $application_key = B2_API_SECRET;
        $credentials = base64_encode($application_key_id . ":" . $application_key);
        $url = self::BASE_URL ."b2_authorize_account";

        // Add headers
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => ['Basic '. $credentials]
        ];

        try {

            $client = $this->client();
            $res = $client->getAsync( $url, ['headers' => $headers])->wait();

            if ($res->getStatusCode() == 200) {
                return json_decode($res->getBody());

            } else {
                throw new \Exception("Error making request ". $res->getBody());
            }

        } catch (\Throwable $ex) {
            throw new \Exception("Error making request ". $ex->getMessage());
        }
    }

    /**
     * @param $api_url
     * @param $auth_token
     * @param $key
     *  The file name of the file to be uploaded
     *
     * @return mixed
     * @throws \Exception
     */
    final public function b2StartLargeFile($api_url, $auth_token, $key) {
        // The content type of the file. See b2_start_large_file documentation for more information.
        $content_type = "b2/x-auto";

        // Provided by b2_create_bucket, b2_list_buckets
        $bucket_id = $this->b2Configs['B2_BUCKET_ID'];

        // Construct the JSON to post
        $data = array("fileName" => $key, "bucketId" => $bucket_id, "contentType" => $content_type);
        $post_fields = json_encode($data);
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => [$auth_token]
        ];

        try {
	        $client = $this->client();
            $res = $client->postAsync($api_url . "/b2api/v2/b2_start_large_file", [
                    'headers' => $headers,
                    'body' => $post_fields,
                ]
            )->wait();

            if ($res->getStatusCode() == 200) {

                return json_decode($res->getBody());
            } else {
                throw new \Exception("Error making request ". $res->getBody());
            }

        } catch (\Throwable $ex) {
            throw new \Exception("Error making request ". $ex->getMessage());
        }

    }

	/**
	 * Gets an URL to use for uploading files.
	 *  https://www.backblaze.com/b2/docs/b2_get_upload_url.html
	 * @param $api_url
	 * @param $auth_token
	 *
	 * @return mixed
	 */
	final public function b2GetUploadUrl($api_url, $auth_token) {

		$bucket_id = $this->b2Configs['B2_BUCKET_ID'];  // The ID of the bucket you want to upload to

		// Get the upload URL
		$client = $this->client();

		$data = ["bucketId" => $bucket_id];
		$headers = [
			'Accept' => 'application/json',
			'Authorization' => [$auth_token]
		];

		$res = $client->postAsync($api_url.  "/b2api/v2/b2_get_upload_url", [
				'headers' => $headers,
				'body' => json_encode($data)
			]
		)->wait();

		// Upload url for a small file.
		// The large_file_auth_token = object ->authorizationToken;
		// The bucketId = object ->bucketId;
		// The upload_url = object ->uploadUrl;
		return json_decode($res->getBody());
	}

	/**
	 * Gets an URL to use for uploading parts of a large file.
	 * docs @ https://www.backblaze.com/b2/docs/b2_get_upload_part_url.html
	 * @param $api_url
	 * @param $auth_token
	 * @param $file_id
	 *
	 * @return mixed
	 */
    final public function b2GetUploadPartUrl($api_url, $auth_token, $file_id) {
	    // Get upload part URL

	    $client = $this->client();

	    $data = ["fileId" => $file_id ];

	    $headers = [
		    'Accept' => 'application/json',
		    'Authorization' => [$auth_token]
	    ];

	    $res = $client->postAsync($api_url. "/b2api/v2/b2_get_upload_part_url", [
			    'headers' => $headers,
			    'body' => json_encode($data)
		    ]
	    )->wait();

	    // Next part upload url.
	    // contains
	    // The large_file_auth_token = object ->authorizationToken;
	    // The upload_url = object ->uploadUrl;
	    return json_decode($res->getBody());

    }

	/**
	 * Uploads one file to B2, returning its unique file ID,
	 * among other items related to the uploaded file.
	 * https://www.backblaze.com/b2/docs/b2_upload_file.html
	 * first get the upload url @ https://www.backblaze.com/b2/docs/b2_get_upload_url.html
	 * @param $file_name
	 *  The file name in the bucket(key)
	 * @param $local_file
	 *   Full path to the local file
	 * @param $upload_url
	 *  Provided by b2_get_upload_url
	 * @param $upload_auth_token
	 *   Provided by b2_get_upload_url
	 *
	 * @return mixed
	 */
	final public function b2UploadFile($file_name, $local_file, $upload_url, $upload_auth_token, ) {

	    $client = $this->client();

	    $handle = fopen($local_file, 'r');
	    $read_file = fread($handle,filesize($local_file));

	    $bucket_id = $this->b2Configs['B2_BUCKET_ID']; ;  // The ID of the bucket
	    $content_type = "text/plain";
	    $sha1_of_file_data = sha1_file($local_file);


	    $headers = [
		    'Authorization' => [$upload_auth_token],
		    'X-Bz-File-Name' => $file_name,
		    'Content-Type' => $content_type,
		    'X-Bz-Content-Sha1' => $sha1_of_file_data,
		    'X-Bz-Info-Author' => "unknown",
		    'X-Bz-Server-Side-Encryption' => "AES256",
	    ];

	    // Add read file as post field
	    $res = $client->postAsync($upload_url, [
			    'headers' => $headers,
			    'body' => $read_file
		    ]
	    )->wait();
	    return json_decode($res->getBody());



    }

	/**
	 * Uploads chucks as well as finish the upload.
	 * https://www.backblaze.com/b2/docs/b2_upload_part.html
	 *
	 * @param $local_file
	 * @param $auth_token
	 * @param $api_url
	 * @param $file_id
	 *
	 * @return mixed|void
	 * @throws \Exception
	 */
	final public function b2UploadChunks($local_file, $auth_token, $api_url, $file_id){
		$client = $this->client();

		try {

			//  5 MB parts but recommended (default) is 100 MB
			$part_size = 5 * 1000 * 1000;
			$local_file_size = filesize($local_file);
			$total_bytes_sent = 0;
			$sha1_of_parts = array();
			$part_no = 1;
			$file_handle = fopen($local_file, "r");

			// Next part upload url.
			// Get upload part URL
			$part_response = $this->b2GetUploadPartUrl($api_url, $auth_token, $file_id);

			// Loop through parts of file
			// retry logic outlined at https://www.backblaze.com/b2/docs/uploading.html
			while ($total_bytes_sent < $local_file_size) {
				// retries;
				$max_retries = 5;
				$retry_count = 0;

				$large_file_auth_token = $part_response->authorizationToken;
				$upload_url = $part_response->uploadUrl;

				$bytes_sent_for_part = min($local_file_size - $total_bytes_sent, $part_size);

				//
				// Upload the part to the URL we just received
				//

				// Get a sha1 of the part we are going to send
				fseek($file_handle, $total_bytes_sent);
				$data_part = fread($file_handle, $bytes_sent_for_part);
				array_push($sha1_of_parts, sha1($data_part));
				fseek($file_handle, $total_bytes_sent);

				// Guzzle overrides some headers, resulting in Bad request,
				// so we use curl for the retries.
				$session = curl_init($upload_url);

				// NOTE - we need to set a blank Transfer-Encoding header, otherwise PHP will
				// default to chunked transfer encoding and remove the content length header
				$headers = array(
					"Accept: application/json",
					"Authorization: $large_file_auth_token",
					"Transfer-Encoding:",
					"Content-Length: $bytes_sent_for_part",
					"X-Bz-Part-Number: $part_no",
					"X-Bz-Content-Sha1: {$sha1_of_parts[$part_no - 1]}"
				);
				curl_setopt($session, CURLOPT_POST, true);
				curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($session, CURLOPT_INFILE, $file_handle);
				curl_setopt($session, CURLOPT_INFILESIZE, (int)$bytes_sent_for_part);
				curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($session, CURLOPT_READFUNCTION, array (&$this, "curlReadFile"));

				while ($retry_count++ < $max_retries) {
					$server_output = curl_exec($session);
					$http_code = curl_getinfo($session, CURLINFO_HTTP_CODE);
					if ($http_code == 200) {
						break;
					} else {
						sleep(5);
					}
				}

				$part_no++;
				$total_bytes_sent += $bytes_sent_for_part;

			}
			fclose($file_handle);

			// Finish the upload

			$data = array(
				"fileId" => $file_id,
				"partSha1Array" => $sha1_of_parts
			);

			$headers = [
				'Accept' => 'application/json',
				'Authorization' => [$auth_token]
			];

			$res = $client->postAsync($api_url."/b2api/v2/b2_finish_large_file", [
					'headers' => $headers,
					'body' => json_encode($data),
				]
			)->wait();

			if ($res->getStatusCode() == 200) {

				return json_decode($res->getBody());
			} else {
				throw new \Exception("Backblaze upload error : ". $res->getBody());
			}


		} catch (\Throwable $exception) {
			throw new \Exception("Backblaze upload error : ".$exception);
		}
	}

	private function curlReadFile($curl_rsrc, $file_pointer, $length) {
		return fread($file_pointer, $length);
	}

}