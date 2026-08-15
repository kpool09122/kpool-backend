<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Query\ListPrincipalGroups;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface ListPrincipalGroupsInputPort
{
    public function accountIdentifier(): AccountIdentifier;
}
