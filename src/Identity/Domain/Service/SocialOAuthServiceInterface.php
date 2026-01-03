<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Service;

use Source\Identity\Domain\ValueObject\OAuthCode;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SocialProfile;
use Source\Identity\Domain\ValueObject\SocialProvider;

interface SocialOAuthServiceInterface
{
    public function buildRedirectUrl(SocialProvider $provider, OAuthState $state): string;

    public function fetchProfile(SocialProvider $provider, OAuthCode $code): SocialProfile;
}
