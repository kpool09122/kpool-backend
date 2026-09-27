<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial;

use Source\Identity\Domain\ValueObject\SocialProvider;

interface StartPasskeyRecoveryWithSocialInputPort
{
    public function provider(): SocialProvider;
}
