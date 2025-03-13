<?php declare(strict_types=1);

namespace Ulovdomov\Digitoo\Client;

use Ulovdomov\Digitoo\Authorization\AccessTokenDTO;
use Ulovdomov\Digitoo\Authorization\AuthorizationManager;
use Ulovdomov\Digitoo\Authorization\Exception\InvalidAccessTokenFormatException;
use Ulovdomov\Digitoo\Client\Exception\ClientException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class DigitooClient
{
	public function __construct(
		private string $apiDomain,
		private string $authorizationApiEndpoint,
		private AuthorizationManager $authorizationManager,
	) {}

	/**
	 * @throws ClientException
	 */
	public function get(string $url, string $body, array $headers = []): ResponseInterface
	{
		return $this->query('GET', $url, $body, $headers);
	}

	/**
	 * @throws ClientException
	 */
	public function post(string $url, string $body, array $headers = []): ResponseInterface
	{
		return $this->query('POST', $url, $body, $headers);
	}

	/**
	 * @throws ClientException
	 */
	public function patch(string $url, string $body, array $headers = []): ResponseInterface
	{
		return $this->query('PATCH', $url, $body, $headers);
	}

	/**
	 * @throws ClientException
	 */
	public function put(string $url, string $body, array $headers = []): ResponseInterface
	{
		return $this->query('PUT', $url, $body, $headers);
	}

	private function query(string $method, string $endpoint, string $body, array $headers): ResponseInterface
	{
		$url = \sprintf('%s/%s', \trim($this->apiDomain, '/'), \trim($endpoint, '/'));

		$options = new HttpOptions();
		$options->setHeaders($headers);
		$options->setBody($body);
		if ($endpoint !== $this->authorizationApiEndpoint) {
			$options->setAuthBearer($this->getAccessToken()->getAccessToken());
		}

		try {
			$client = HttpClient::create();
			$response = $client->request($method, $url, $options->toArray());
		} catch (TransportExceptionInterface $e) {
			throw ClientException::createFromPrevious($e);
		}

		try {

			if ($response->getStatusCode() === 401) {
				$this->authorizationManager->clearAccessToken();
				$options->setAuthBearer($this->getAccessToken()->getAccessToken());
				$response = $client->request($method, $url, $options->toArray());
			}

			if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 201) {
				$content = $response->toArray(false);

				throw ClientException::invalidStatusCode(
					$response->getStatusCode(),
					$url,
					$method,
					'',
					$content['message'] ?? $response->getContent(false),
					$content,
				);
			}
		} catch (ExceptionInterface $e) {
			throw ClientException::createFromPrevious($e);
		}

		return $response;
	}

	private function getAccessToken(): AccessTokenDTO
	{
		$accessToken = $this->authorizationManager->loadAccessToken();
		if ($accessToken !== null) {
			return $accessToken;
		}

		$response = $this->post($this->authorizationApiEndpoint, $this->authorizationManager->getAuthorizationBody(), ['Content-Type' => 'application/json']);

		try {
			$accessToken = AccessTokenDTO::createFromDecodedResponse(Json::decode($response->getContent())->data);
		} catch (JsonException|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface|InvalidAccessTokenFormatException $e) {
			throw ClientException::createFromPrevious($e);
		}
		$this->authorizationManager->saveAccessToken($accessToken);

		return $accessToken;
	}
}
