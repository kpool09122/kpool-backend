<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\StartStepUpWithSocial;

use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Domain\ValueObject\StepUpReturnDestination;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface StartStepUpWithSocialInputPort
{
    public function identityIdentifier(): IdentityIdentifier;

    public function provider(): SocialProvider;

    public function returnDestination(): StepUpReturnDestination;
}
