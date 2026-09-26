<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreateStepUpPasskeyOptions;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface CreateStepUpPasskeyOptionsInputPort
{
    public function identityIdentifier(): IdentityIdentifier;
}
