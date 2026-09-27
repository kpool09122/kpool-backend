<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial;

use Source\Identity\Domain\ValueObject\SocialProvider;

readonly class StartPasskeyRecoveryWithSocialInput implements StartPasskeyRecoveryWithSocialInputPort
{
    public function __construct(
        private SocialProvider $provider,
    ) {
    }

    public function provider(): SocialProvider
    {
        return $this->provider;
    }
}
