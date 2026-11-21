<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\UseCase\Query\ListMembers;

use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface ListMembersInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
