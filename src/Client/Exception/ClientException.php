<?php declare(strict_types=1);

namespace Ulovdomov\Digitoo\Client\Exception;

use Throwable;

final class ClientException extends \RuntimeException
{

	protected function __construct(string $message = '', int $code = 0, Throwable|null $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}

	public static function createFromPrevious(Throwable $e): self
	{
		return new self($e->getMessage(), $e->getCode(), $e);
	}

	/**
	 * @param array<string> $response
	 */
	public static function invalidStatusCode(
		int $code,
		string $endpoint,
		string $method,
		string $request,
		string $message,
		array $response,
	): self {
		$e = new self(
			\sprintf(
				'Invalid response status code: %s, endpoint: %s, method: %s, request: %s, response: %s',
				$code,
				$endpoint,
				$method,
				$request,
				$message,
			),
		);

		$e->response = $response;

		return $e;
	}
}
