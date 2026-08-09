<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Query\ListMembers;

use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class ListMembersInput implements ListMembersInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private Principal $principal,
        private ?AccountType $accountType = null,
    )
    {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }

    public function accountType(): ?AccountType
    {
        return $this->accountType;
    }
}
