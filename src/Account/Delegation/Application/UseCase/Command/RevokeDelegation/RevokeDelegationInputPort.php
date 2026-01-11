<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\RevokeDelegation;

use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface RevokeDelegationInputPort
{
    public function delegationIdentifier(): DelegationIdentifier;

    public function revokerIdentifier(): IdentityIdentifier;
}
