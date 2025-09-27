<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\RejectUpdatedMember;

use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;

interface RejectUpdatedMemberInputPort
{
    public function memberIdentifier(): MemberIdentifier;
}
