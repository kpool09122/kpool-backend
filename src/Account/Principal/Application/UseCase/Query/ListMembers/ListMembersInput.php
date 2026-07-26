<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Query\ListMembers;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class ListMembersInput implements ListMembersInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier, private Principal $principal)
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
}
