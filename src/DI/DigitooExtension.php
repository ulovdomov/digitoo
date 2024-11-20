<?php declare(strict_types=1);

namespace Ulovdomov\Digitoo\DI;

use Ulovdomov\Digitoo\Authorization\AuthorizationManager;
use Ulovdomov\Digitoo\Authorization\CacheAuthorizationManager;
use Ulovdomov\Digitoo\Client\DigitooClient;
use Ulovdomov\Digitoo\UseCase\Queue\UploadFilesToQueueUseCase;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

final class DigitooExtension extends CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure(
			[
				'apiDomain' => Expect::string()->default('https://api.digitoo.ai/'),
				'authorizationApiEndpoint' => Expect::string()->default('/api/v2/auth/login'),
				'email' => Expect::string()->default(''),
				'password' => Expect::string()->default(''),
			],
		);
	}

	public function loadConfiguration(): void
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();
		$builder->addDefinition('digitoo.client')
			->setFactory(DigitooClient::class)
			->setArguments([
				'apiDomain' => $config->apiDomain,
				'authorizationApiEndpoint' => $config->authorizationApiEndpoint,
			]);
		$builder->addDefinition('digitoo.cacheAuthorizationManager')
			->setType(AuthorizationManager::class)
			->setFactory(CacheAuthorizationManager::class)
			->setArguments([
				'email' => $config->email,
				'password' => $config->password,
			]);
		$builder->addDefinition('digitoo.uploadFilesToQueueUseCase')
			->setFactory(UploadFilesToQueueUseCase::class);
	}
}
