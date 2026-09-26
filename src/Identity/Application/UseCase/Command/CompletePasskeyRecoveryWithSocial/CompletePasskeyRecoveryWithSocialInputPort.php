<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CompletePasskeyRecoveryWithSocial;

use Source\Identity\Domain\ValueObject\OAuthCode;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SocialProvider;

interface CompletePasskeyRecoveryWithSocialInputPort
{
    public function provider(): SocialProvider;

    public function code(): OAuthCode;

    public function state(): OAuthState;
}
