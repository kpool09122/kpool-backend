<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreateStepUpPasskeyOptions;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class CreateStepUpPasskeyOptionsInput implements CreateStepUpPasskeyOptionsInputPort
{
    public function __construct(private IdentityIdentifier $identityIdentifier)
    {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }
}
