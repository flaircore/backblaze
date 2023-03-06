<?php

namespace Flaircore\Backblaze\Tests\Unit;

use Flaircore\Backblaze\Clients\HttpClient;
use GuzzleHttp\Client;

use PHPUnit\Framework\TestCase;

/**
 * Tests HttpClient trait.
 */
class HttpClientTest extends TestCase {

	public function testReturnsAClient() {
		// returns a Guzzle Http client instance.
		$trait = $this->getObjectForTrait(HttpClient::class);

		self::assertIsObject($trait->client());
		self::assertInstanceOf(Client::class, $trait->client());
	}
}