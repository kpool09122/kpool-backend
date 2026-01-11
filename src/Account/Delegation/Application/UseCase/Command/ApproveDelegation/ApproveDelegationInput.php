<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation;

use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class ApproveDelegationInput implements ApproveDelegationInputPort
{
    public function __construct(
        private DelegationIdentifier $delegationIdentifier,
        private IdentityIdentifier $approverIdentifier,
    ) {
    }

    public function delegationIdentifier(): DelegationIdentifier
    {
        return $this->delegationIdentifier;
    }

    public function approverIdentifier(): IdentityIdentifier
    {
        return $this->approverIdentifier;
    }
}
