<?php declare(strict_types=1);

namespace Ulovdomov\Digitoo\Authorization;

use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Throwable;

final class CacheAuthorizationManager implements AuthorizationManager
{
	private const ACCESS_TOKEN_CACHE_KEY = 'digitoo_access_token_cache_key';
	private Cache $cache;

	public function __construct(
		private string $email,
		private string $password,
		Storage $storage,
	) {
		$this->cache = new Cache($storage);
	}

	/**
	 * @throws Throwable
	 */
	public function loadAccessToken(): ?AccessTokenDTO
	{
		$accessToken = $this->cache->load(self::ACCESS_TOKEN_CACHE_KEY);
		if ($accessToken === null) {
			return null;
		}

		return AccessTokenDTO::createFromJson($accessToken);
	}

	/**
	 * @throws JsonException
	 */
	public function saveAccessToken(AccessTokenDTO $accessToken): void
	{
		$now = new DateTime('now');
		$diff = $accessToken->getExpiresAt()->diff($now);
		$diff = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
		$this->cache->save(
			self::ACCESS_TOKEN_CACHE_KEY,
			$accessToken->toJson(),
			[
				Cache::EXPIRATION => $diff . ' minutes',
			],
		);
		$this->cache->save(self::ACCESS_TOKEN_CACHE_KEY, $accessToken->toJson());
	}

	public function clearAccessToken(): void
	{
		$this->cache->remove(self::ACCESS_TOKEN_CACHE_KEY);
	}

	/**
	 * @throws JsonException
	 */
	public function getAuthorizationBody(): string
	{
		return Json::encode([
			'email' => $this->email,
			'password' => $this->password,
		]);
	}
}
