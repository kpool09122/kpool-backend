<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Query\ListPrincipalGroups;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class ListPrincipalGroupsInput implements ListPrincipalGroupsInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier, private PrincipalIdentifier $operatorPrincipalIdentifier)
    {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function operatorPrincipalIdentifier(): PrincipalIdentifier
    {
        return $this->operatorPrincipalIdentifier;
    }
}
