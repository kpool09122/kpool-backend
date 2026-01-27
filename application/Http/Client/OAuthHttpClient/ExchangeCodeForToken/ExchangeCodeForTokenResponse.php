<?php

declare(strict_types=1);

namespace Application\Http\Client\OAuthHttpClient\ExchangeCodeForToken;

final readonly class ExchangeCodeForTokenResponse
{
    public function __construct(
        private string $accessToken,
        private ?string $idToken,
    ) {
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function idToken(): ?string
    {
        return $this->idToken;
    }
}
