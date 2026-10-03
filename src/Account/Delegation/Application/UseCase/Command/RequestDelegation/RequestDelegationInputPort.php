<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\RequestDelegation;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface RequestDelegationInputPort
{
    public function principal(): Principal;

    public function targetAccountIdentifier(): AccountIdentifier;
}
