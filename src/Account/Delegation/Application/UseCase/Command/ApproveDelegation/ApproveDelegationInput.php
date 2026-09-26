<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

readonly class ApproveDelegationInput implements ApproveDelegationInputPort
{
    public function __construct(private DelegationIdentifier $delegationIdentifier, private Principal $principal)
    {
    }

    public function delegationIdentifier(): DelegationIdentifier
    {
        return $this->delegationIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
