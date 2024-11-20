<?php declare(strict_types=1);

namespace Ulovdomov\Digitoo\Authorization;

interface AuthorizationManager
{
	public function loadAccessToken(): ?AccessTokenDTO;

	public function saveAccessToken(AccessTokenDTO $accessToken): void;

	public function getAuthorizationBody(): string;
}
