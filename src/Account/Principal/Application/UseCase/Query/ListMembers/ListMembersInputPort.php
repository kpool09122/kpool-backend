<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Query\ListMembers;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface ListMembersInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function principal(): Principal;
}
