<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\SubmitMember;

use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;

interface SubmitMemberInputPort
{
    public function memberIdentifier(): MemberIdentifier;
}
