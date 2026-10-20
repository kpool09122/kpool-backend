<?php

declare(strict_types=1);

namespace Application\Http\Client\GoogleTranslateClient;

use Google\Auth\Credentials\ServiceAccountCredentials;
use InvalidArgumentException;
use JsonException;

final readonly class GoogleTranslateCredentials
{
    /** @var array<string, mixed> */
    private array $data;

    public function __construct(private string $projectId, string $credentials)
    {
        $this->data = self::decode($credentials);
    }

    public function projectId(): string
    {
        $projectId = $this->data['project_id'] ?? '';

        return $this->projectId !== '' ? $this->projectId : (is_string($projectId) ? $projectId : '');
    }

    public function isConfigured(): bool
    {
        $data = $this->data;

        return $this->projectId() !== ''
            && ($data['type'] ?? null) === 'service_account'
            && is_string($data['client_email'] ?? null) && $data['client_email'] !== ''
            && is_string($data['private_key'] ?? null) && $data['private_key'] !== '';
    }

    public function create(): ServiceAccountCredentials
    {
        if (! $this->isConfigured()) {
            throw new InvalidArgumentException('Google Translate service account credentials are not configured');
        }

        return new ServiceAccountCredentials('https://www.googleapis.com/auth/cloud-translation', $this->data);
    }

    /** @return array<string, mixed> */
    private static function decode(string $json): array
    {
        if ($json === '') {
            return [];
        }

        if (! str_starts_with(ltrim($json), '{')) {
            if (! is_file($json) || ! is_readable($json)) {
                return [];
            }
            $json = file_get_contents($json);
            if ($json === false) {
                return [];
            }
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        if (! is_array($data) || array_is_list($data)) {
            return [];
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}
