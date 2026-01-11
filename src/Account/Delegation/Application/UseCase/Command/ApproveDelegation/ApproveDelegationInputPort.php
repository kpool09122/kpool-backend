<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation;

use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface ApproveDelegationInputPort
{
    public function delegationIdentifier(): DelegationIdentifier;

    public function approverIdentifier(): IdentityIdentifier;
}
