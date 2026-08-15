<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Query\ListPrincipalGroups;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class ListPrincipalGroupsInput implements ListPrincipalGroupsInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier)
    {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }
}
