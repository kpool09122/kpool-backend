<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\TranslateMember;

use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;

interface TranslateMemberInputPort
{
    public function memberIdentifier(): MemberIdentifier;

    public function principal(): Principal;
}
