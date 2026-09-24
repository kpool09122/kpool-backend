<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\AddPasskeyOptions;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AddPasskeyOptionsInput implements AddPasskeyOptionsInputPort
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
