<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\StartStepUpWithSocial;

use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Domain\ValueObject\StepUpReturnDestination;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class StartStepUpWithSocialInput implements StartStepUpWithSocialInputPort
{
    public function __construct(private IdentityIdentifier $identityIdentifier, private SocialProvider $provider, private StepUpReturnDestination $returnDestination)
    {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function returnDestination(): StepUpReturnDestination
    {
        return $this->returnDestination;
    }

    public function provider(): SocialProvider
    {
        return $this->provider;
    }
}
