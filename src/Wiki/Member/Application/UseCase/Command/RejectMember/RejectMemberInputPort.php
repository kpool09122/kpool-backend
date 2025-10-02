<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\RejectMember;

use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;

interface RejectMemberInputPort
{
    public function memberIdentifier(): MemberIdentifier;
}
