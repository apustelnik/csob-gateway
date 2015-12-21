<?php

namespace SlevomatCsobGateway;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use SlevomatCsobGateway\Api\ApiClientDriver;
use SlevomatCsobGateway\Api\Driver\GuzzleDriver;
use SlevomatCsobGateway\Api\HttpMethod;
use SlevomatCsobGateway\Api\Response;
use SlevomatCsobGateway\Api\ResponseCode;

/**
 * @author Jiří Pudil <me@jiripudil.cz>
 */
class ApiDriverMock implements ApiClientDriver
{

	/**
	 * @var GuzzleDriver
	 */
	private $realClient;

	public function __construct()
	{
		$this->realClient = new GuzzleDriver(new Client([
			RequestOptions::ALLOW_REDIRECTS => false,
			RequestOptions::HTTP_ERRORS => false,
		]));
	}

	/**
	 * @param \SlevomatCsobGateway\Api\HttpMethod $method
	 * @param string $url
	 * @param array|null $data
	 * @param array $headers
	 * @return \SlevomatCsobGateway\Api\Response
	 */
	public function request(HttpMethod $method, $url, array $data = null, array $headers = [])
	{
		$targetFile = $this->resolveTargetFile($method, $url, $headers, $data);

		if (!file_exists($targetFile)) {
			$response = $this->realClient->request($method, $url, $data, $headers);
			$data = [
				'status' => $response->getResponseCode()->getValue(),
				'headers' => $response->getHeaders(),
			];

			$responseBody = $response->getData();
			$data['body'] = $responseBody;

			file_put_contents($targetFile, json_encode($data, JSON_PRETTY_PRINT));

		} else {
			$data = json_decode(file_get_contents($targetFile), true);
			$response = new Response(new ResponseCode($data['status']), $data['body'], $data['headers']);
		}

		return $response;
	}

	private function resolveTargetFile($method, $url, $headers, $body)
	{
		$parsedUrl = parse_url($url);
		$path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';

		list(, , , $resource, $action) = explode('/', $path);
		$endpoint = $resource . '/' . $action;

		switch ($endpoint) {
			case 'payment/init':
				return __DIR__ . '/api-data/init_' . $body['merchantId'] . '_' . $body['orderNo'] . '.json';

			case 'payment/recurrent':
				return __DIR__ . '/api-data/recurrent_' . $body['merchantId'] . '_' . $body['origPayId'] . '.json';

			case 'payment/close':
			case 'payment/reverse':
			case 'payment/refund':
				return __DIR__ . '/api-data/' . $action . '_' . $body['merchantId'] . '_' . $body['payId'] . '.json';

			case 'payment/process':
			case 'payment/status':
				list(, , , , , $merchantId, $payId) = explode('/', $path);

				return __DIR__ . '/api-data/' . $action . '_' . $merchantId . '_' . $payId . '.json';

			case 'customer/info':
				list(, , , , , $merchantId, $customerId) = explode('/', $path);

				return __DIR__ . '/api-data/customer_' . $merchantId . '_' . $customerId . '.json';

			case 'payment/400':
			case 'payment/403':
			case 'payment/404':
			case 'payment/429':
			case 'payment/503':
				return __DIR__ . '/api-data/error_' . $action . '.json';

			default:
				throw new \LogicException(sprintf('Unexpected %s to endpoint %s', $method, $endpoint));
		}
	}

}
