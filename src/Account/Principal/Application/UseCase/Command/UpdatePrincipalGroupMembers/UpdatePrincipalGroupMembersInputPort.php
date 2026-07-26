<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface UpdatePrincipalGroupMembersInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function principal(): Principal;

    /**
     * @return array<int, PrincipalGroupMembers>
     */
    public function principalGroups(): array;
}
