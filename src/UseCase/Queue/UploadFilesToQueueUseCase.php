<?php declare(strict_types=1);

namespace Ulovdomov\Digitoo\UseCase\Queue;

use Ulovdomov\Digitoo\Client\DigitooClient;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

final class UploadFilesToQueueUseCase
{
	public function __construct(
		private DigitooClient $client,
	) {}

	public function execute(string $path, string $filename, string $contentType, string $queueId): void
	{
		$url = \sprintf('/api/v2/queues/%s/upload', $queueId);
		$formFields = [
			'files' => DataPart::fromPath($path, $filename, $contentType),
		];
		$formData = new FormDataPart($formFields);
		$headers = $formData->getPreparedHeaders()->toArray();

		$body = $formData->bodyToString();
		$this->client->post($url, $body, $headers);
	}
}
