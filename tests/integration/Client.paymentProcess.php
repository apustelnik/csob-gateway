<?php

namespace SlevomatCsobGateway;

use Kdyby\CsobPaymentGateway\Client;
use Kdyby\CsobPaymentGateway\Configuration;
use Kdyby\CsobPaymentGateway\Http\GuzzleClient;
use Kdyby\CsobPaymentGateway\InvalidParameterException;
use Kdyby\CsobPaymentGateway\Message\RedirectResponse;
use Kdyby\CsobPaymentGateway\Message\Signature;
use Kdyby\CsobPaymentGateway\Payment;
use SlevomatCsobGateway\Api\HttpMethod;
use SlevomatCsobGateway\Call\PaymentStatus;
use SlevomatCsobGateway\Call\PayMethod;
use SlevomatCsobGateway\Call\PayOperation;
use Tester;
use Tester\Assert;

class ClientPaymentProcessTest extends CsobTestCase
{

	public function testPaymentProcess()
	{
		$cart = new Cart(new Currency(Currency::CZK));
		$cart->addItem('Purchase', 1, 39000);
		$cart->addItem('Purchase', 2, 45000);
		$response = $this->requestFactory->createInitPayment(
			'12345',
			new PayOperation(PayOperation::PAYMENT),
			new PayMethod(PayMethod::CARD),
			true,
			'https://slevomat.cz/receive-response',
			new HttpMethod(HttpMethod::POST),
			$cart,
			'Payment',
			null,
			null,
			new Language(Language::CZ)
		)->send($this->client);


		self::assertNotNull($response->getPayId());
		self::assertSame(0, $response->getResultCode()->getValue());
		self::assertSame('OK', $response->getResultMessage());
		self::assertSame(PaymentStatus::S1_CREATED, $response->getPaymentStatus()->getValue());

		$processResponse = $this->requestFactory->createProcessPayment($response->getPayId())
			->send($this->client);
		self::stringStartsWith('https://iplatebnibrana.csob.cz')->evaluate($processResponse->getGatewayLocationUrl());
	}

}
