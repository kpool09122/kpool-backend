<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\TranslateMember;

use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;

interface TranslateMemberInputPort
{
    public function memberIdentifier(): MemberIdentifier;
}
