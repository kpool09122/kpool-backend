<?php

declare(strict_types=1);

namespace Application\Http\Client\OAuthHttpClient\ExchangeCodeForToken;

final readonly class ExchangeCodeForTokenParams
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        private array $params,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function fromArray(array $params): self
    {
        return new self($params);
    }

    public function accessToken(): string
    {
        /** @var string $accessToken */
        $accessToken = $this->params['access_token'];

        return $accessToken;
    }

    public function idToken(): ?string
    {
        /** @var string|null $idToken */
        $idToken = $this->params['id_token'] ?? null;

        return $idToken;
    }
}
