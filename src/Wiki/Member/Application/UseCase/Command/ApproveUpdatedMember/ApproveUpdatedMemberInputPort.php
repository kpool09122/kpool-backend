<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\ApproveUpdatedMember;

use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;

interface ApproveUpdatedMemberInputPort
{
    public function memberIdentifier(): MemberIdentifier;

    public function publishedMemberIdentifier(): ?MemberIdentifier;
}
