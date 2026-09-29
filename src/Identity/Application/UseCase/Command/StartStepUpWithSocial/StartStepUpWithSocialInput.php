<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\StartStepUpWithSocial;

use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class StartStepUpWithSocialInput implements StartStepUpWithSocialInputPort
{
    public function __construct(private IdentityIdentifier $identityIdentifier, private SocialProvider $provider)
    {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function provider(): SocialProvider
    {
        return $this->provider;
    }
}
