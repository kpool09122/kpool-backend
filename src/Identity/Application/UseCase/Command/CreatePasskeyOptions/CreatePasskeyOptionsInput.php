<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyOptions;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class CreatePasskeyOptionsInput implements CreatePasskeyOptionsInputPort
{
    public function __construct(
        private IdentityIdentifier $identityIdentifier,
    ) {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }
}
