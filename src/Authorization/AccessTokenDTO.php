<?php declare(strict_types=1);

namespace Ulovdomov\Digitoo\Authorization;

use Ulovdomov\Digitoo\Authorization\Exception\InvalidAccessTokenFormatException;
use Exception;
use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use stdClass;

final class AccessTokenDTO
{
	public function __construct(
		private string $accessToken,
		private string $refreshToken,
		private DateTime $expiresAt,
	) {}

	/**
	 * @throws InvalidAccessTokenFormatException
	 */
	public static function createFromDecodedResponse(stdClass $decodedResponse): self
	{
		try {
			return new self(
				accessToken: $decodedResponse->access_token,
				refreshToken: $decodedResponse->refresh_token,
				expiresAt: new DateTime($decodedResponse->expires_at),
			);
		} catch (\Exception) {
			throw new InvalidAccessTokenFormatException('Invalid access token format');
		}
	}

	/**
	 * @param string[] $data
	 * @throws Exception
	 */
	public static function createFromArray(array $data): self
	{
		return new self(
			accessToken: $data['access_token'],
			refreshToken: $data['refresh_token'],
			expiresAt: new DateTime($data['expires_at']),
		);
	}

	/**
	 * @throws InvalidAccessTokenFormatException
	 * @throws JsonException
	 */
	public static function createFromJson(string $accessToken): self
	{
		return self::createFromDecodedResponse(Json::decode($accessToken));
	}

	public function getAccessToken(): string
	{
		return $this->accessToken;
	}

	public function getRefreshToken(): string
	{
		return $this->refreshToken;
	}

	public function getExpiresAt(): DateTime
	{
		return $this->expiresAt;
	}

	/**
	 * @return string[]
	 */
	public function toArray(): array
	{
		return [
			'access_token' => $this->accessToken,
			'refresh_token' => $this->refreshToken,
			'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
		];
	}

	/**
	 * @throws JsonException
	 */
	public function toJson(): string
	{
		return Json::encode($this->toArray());
	}
}
