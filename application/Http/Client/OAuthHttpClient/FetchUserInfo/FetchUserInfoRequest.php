<?php

declare(strict_types=1);

namespace Application\Http\Client\OAuthHttpClient\FetchUserInfo;

use Source\Identity\Domain\ValueObject\SocialProvider;

final readonly class FetchUserInfoRequest
{
    public function __construct(
        private SocialProvider $provider,
        private string $accessToken,
    ) {
    }

    public function provider(): SocialProvider
    {
        return $this->provider;
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }
}
