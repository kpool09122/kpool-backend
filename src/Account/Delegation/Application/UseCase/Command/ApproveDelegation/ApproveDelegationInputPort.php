<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

interface ApproveDelegationInputPort
{
    public function delegationIdentifier(): DelegationIdentifier;

    public function principal(): Principal;
}
