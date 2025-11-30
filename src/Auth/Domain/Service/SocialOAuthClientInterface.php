<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Service;

use Source\Auth\Domain\ValueObject\OAuthCode;
use Source\Auth\Domain\ValueObject\OAuthState;
use Source\Auth\Domain\ValueObject\SocialProfile;
use Source\Auth\Domain\ValueObject\SocialProvider;

interface SocialOAuthClientInterface
{
    public function buildRedirectUrl(SocialProvider $provider, OAuthState $state): string;

    public function fetchProfile(SocialProvider $provider, OAuthCode $code): SocialProfile;
}
