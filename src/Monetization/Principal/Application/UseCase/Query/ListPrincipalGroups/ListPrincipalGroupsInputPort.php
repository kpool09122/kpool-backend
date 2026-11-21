<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\UseCase\Query\ListPrincipalGroups;

use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface ListPrincipalGroupsInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
