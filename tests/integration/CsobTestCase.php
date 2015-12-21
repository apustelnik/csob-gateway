<?php

namespace SlevomatCsobGateway;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use SlevomatCsobGateway\Api\ApiClient;
use SlevomatCsobGateway\Api\Driver\GuzzleDriver;
use SlevomatCsobGateway\Crypto\CryptoService;

class CsobTestCase extends \PHPUnit_Framework_TestCase
{

	/**
	 * @var ApiClient
	 */
	protected $client;

	/** @var RequestFactory */
	protected $requestFactory;

	protected function setUp()
	{
		parent::setUp();
		$this->client = new ApiClient(
			new ApiDriverMock(),
			new CryptoService(
				__DIR__ . '/../keys/client.key',
				__DIR__ . '/../keys/bank.pub'
			),

			'https://iapi.iplatebnibrana.csob.cz/api/v1.5'
		);
		$this->requestFactory = new RequestFactory('A1487cFZHw');
	}

}
