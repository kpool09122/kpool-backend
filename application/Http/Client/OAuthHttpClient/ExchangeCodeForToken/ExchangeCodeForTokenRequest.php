<?php

declare(strict_types=1);

namespace Application\Http\Client\OAuthHttpClient\ExchangeCodeForToken;

use Source\Identity\Domain\ValueObject\SocialProvider;

final readonly class ExchangeCodeForTokenRequest
{
    public function __construct(
        private SocialProvider $provider,
        private string $code,
    ) {
    }

    public function provider(): SocialProvider
    {
        return $this->provider;
    }

    public function code(): string
    {
        return $this->code;
    }
}
