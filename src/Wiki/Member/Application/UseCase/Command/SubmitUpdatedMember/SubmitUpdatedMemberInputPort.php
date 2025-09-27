<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\SubmitUpdatedMember;

use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;

interface SubmitUpdatedMemberInputPort
{
    public function memberIdentifier(): MemberIdentifier;
}
