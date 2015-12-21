<?php

namespace KdybyTests\CsobPaymentGateway;

use Kdyby\CsobPaymentGateway\Certificate\PrivateKey;
use Kdyby\CsobPaymentGateway\Certificate\PublicKey;
use Kdyby\CsobPaymentGateway\Client;
use Kdyby\CsobPaymentGateway\Configuration;
use Kdyby\CsobPaymentGateway\Message\Signature;
use SlevomatCsobGateway\Api\ApiClient;
use SlevomatCsobGateway\Crypto\CryptoService;
use SlevomatCsobGateway\RequestFactory;
use Tester;



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
				__DIR__ . '/../../keys/client.key',
				__DIR__ . '/../../keys/bank.pub'
			),

			'https://iapi.iplatebnibrana.csob.cz/'
		);
		$this->requestFactory = new RequestFactory('M123');
	}

}
