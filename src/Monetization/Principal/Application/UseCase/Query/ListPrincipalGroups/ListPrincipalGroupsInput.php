<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\UseCase\Query\ListPrincipalGroups;

use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class ListPrincipalGroupsInput implements ListPrincipalGroupsInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier, private PrincipalIdentifier $principalIdentifier)
    {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
