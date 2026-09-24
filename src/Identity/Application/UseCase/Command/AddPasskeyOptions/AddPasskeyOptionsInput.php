<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\AddPasskeyOptions;

use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AddPasskeyOptionsInput implements AddPasskeyOptionsInputPort
{
    public function __construct(
        private IdentityIdentifier $identityIdentifier,
        private ?DelegationIdentifier $delegationIdentifier,
        private ?IdentityIdentifier $originalIdentityIdentifier,
    ) {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function delegationIdentifier(): ?DelegationIdentifier
    {
        return $this->delegationIdentifier;
    }

    public function originalIdentityIdentifier(): ?IdentityIdentifier
    {
        return $this->originalIdentityIdentifier;
    }
}
