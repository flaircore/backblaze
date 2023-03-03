<?php

namespace Flaircore\Backblaze\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RetryMiddleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

trait HttpClient {
	private $MAX_RETRIES = 5;

	private $BASE_URL = 'https://api.backblazeb2.com/b2api/v2/';

	/**
	 * @var Client
	 */
	private $httpClient;
	public function __construct() {

		$maxRetries = $this->MAX_RETRIES;
		$decider = function(int $retries, RequestInterface $request, ResponseInterface $response = null) use ($maxRetries) : bool {
			return
				$retries < $maxRetries
				&& null !== $response
				&& 429 === $response->getStatusCode();
		};

		$delay = function(int $retries, ResponseInterface $response) : int {
			if (!$response->hasHeader('Retry-After')) {
				return RetryMiddleware::exponentialDelay($retries);
			}

			$retryAfter = $response->getHeaderLine('Retry-After');

			if (!is_numeric($retryAfter)) {
				$retryAfter = (new \DateTime($retryAfter))->getTimestamp() - time();
			}

			return (int) $retryAfter * 1000;
		};

		$stack = HandlerStack::create();
		$stack->push(Middleware::retry($decider, $delay));

		$this->httpClient = new Client([
			'base_uri' => 'https://api.backblazeb2.com/b2api/v2/',
			//'handler'  => $stack,
		]);
	}

	/**
	 * @return Client
	 */
	public function client(){
		return $this->httpClient;
	}

}