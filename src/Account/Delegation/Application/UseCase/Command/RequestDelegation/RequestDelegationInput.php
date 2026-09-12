<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\RequestDelegation;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class RequestDelegationInput implements RequestDelegationInputPort
{
    public function __construct(private Principal $principal, private AccountIdentifier $targetAccountIdentifier)
    {
    }

    public function principal(): Principal
    {
        return $this->principal;
    }

    public function targetAccountIdentifier(): AccountIdentifier
    {
        return $this->targetAccountIdentifier;
    }
}
