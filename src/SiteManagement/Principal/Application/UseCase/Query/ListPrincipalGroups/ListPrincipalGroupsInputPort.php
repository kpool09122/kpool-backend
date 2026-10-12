<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Query\ListPrincipalGroups;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface ListPrincipalGroupsInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function operatorPrincipalIdentifier(): PrincipalIdentifier;
}
