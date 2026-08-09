<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Query\ListPrincipalGroups;

use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface ListPrincipalGroupsInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function principal(): Principal;

    public function accountType(): ?AccountType;
}
