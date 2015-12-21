<?php

namespace KdybyTests\CsobPaymentGateway;

use GuzzleHttp\Psr7\Response;
use Kdyby\CsobPaymentGateway\Http\GuzzleClient;
use Kdyby\CsobPaymentGateway\IHttpClient;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Psr\Http\Message\ResponseInterface;
use SlevomatCsobGateway\Api\Driver\CurlDriver;
use SlevomatCsobGateway\Api\HttpMethod;

/**
 * @author Jiří Pudil <me@jiripudil.cz>
 */
class ApiDriverMock implements \SlevomatCsobGateway\Api\ApiClientDriver
{

	/**
	 * @var \SlevomatCsobGateway\Api\Driver\CurlDriver
	 */
	private $realClient;



	public function __construct()
	{
		$this->realClient = new CurlDriver();
	}

	/**
	 * @param \SlevomatCsobGateway\Api\HttpMethod $method
	 * @param string $url
	 * @param array $queries
	 * @param array|null $data
	 * @param array $headers
	 * @return \GuzzleHttp\Psr7\Response|\SlevomatCsobGateway\Api\Response
	 */
	public function request(HttpMethod $method, $url, array $queries = [], array $data = null, array $headers = [])
	{
		$targetFile = $this->resolveTargetFile($method, $url, $headers, $data);

		if (!file_exists($targetFile)) {
			$response = $this->realClient->request($method, $url, $data, $headers);
			$data = [
				'status' => $response->getResponseCode(),
				'headers' => $response->getHeaders(),
			];

			$responseBody = $response->getData();
			$data['body'] = json_decode($responseBody, TRUE);
			if (json_last_error() !== JSON_ERROR_NONE) {
				$data['body'] = $responseBody;
			}

			file_put_contents($targetFile, json_encode($data, JSON_PRETTY_PRINT));

		} else {
			$data = json_decode(file_get_contents($targetFile), TRUE);
			$response = new Response($data['status'], $data['headers'], json_encode($data['body']));
		}

		return $response;
	}



	private function resolveTargetFile($method, $url, $headers, $body)
	{
		$parsedUrl = parse_url($url);
		$path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';

		list(,,, $resource, $action) = explode('/', $path);
		$endpoint = $resource . '/' . $action;

		switch ($endpoint) {
			case 'payment/init':
				$decodedBody = json_decode($body, TRUE);
				return __DIR__ . '/api-data/init_' . $decodedBody['merchantId'] . '_' . $decodedBody['orderNo'] . '.json';

			case 'payment/recurrent':
				$decodedBody = json_decode($body, TRUE);
				return __DIR__ . '/api-data/recurrent_' . $decodedBody['merchantId'] . '_' . $decodedBody['origPayId'] . '.json';

			case 'payment/close':
			case 'payment/reverse':
			case 'payment/refund':
				$decodedBody = json_decode($body, TRUE);
				return __DIR__ . '/api-data/' . $action . '_' . $decodedBody['merchantId'] . '_' . $decodedBody['payId'] . '.json';

			case 'payment/process':
			case 'payment/status':
				list(,,,,, $merchantId, $payId) = explode('/', $path);
				return __DIR__ . '/api-data/' . $action . '_' . $merchantId . '_' . $payId . '.json';

			case 'customer/info':
				list(,,,,, $merchantId, $customerId) = explode('/', $path);
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
