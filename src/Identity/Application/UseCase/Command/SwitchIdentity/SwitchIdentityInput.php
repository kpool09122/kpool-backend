<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SwitchIdentity;

use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class SwitchIdentityInput implements SwitchIdentityInputPort
{
    public function __construct(
        private IdentityIdentifier $currentIdentityIdentifier,
        private ?DelegationIdentifier $targetDelegationIdentifier,
    ) {
    }

    public function currentIdentityIdentifier(): IdentityIdentifier
    {
        return $this->currentIdentityIdentifier;
    }

    public function targetDelegationIdentifier(): ?DelegationIdentifier
    {
        return $this->targetDelegationIdentifier;
    }
}
