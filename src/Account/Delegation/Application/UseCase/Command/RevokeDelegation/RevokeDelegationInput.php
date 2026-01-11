<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\RevokeDelegation;

use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class RevokeDelegationInput implements RevokeDelegationInputPort
{
    public function __construct(
        private DelegationIdentifier $delegationIdentifier,
        private IdentityIdentifier $revokerIdentifier,
    ) {
    }

    public function delegationIdentifier(): DelegationIdentifier
    {
        return $this->delegationIdentifier;
    }

    public function revokerIdentifier(): IdentityIdentifier
    {
        return $this->revokerIdentifier;
    }
}
