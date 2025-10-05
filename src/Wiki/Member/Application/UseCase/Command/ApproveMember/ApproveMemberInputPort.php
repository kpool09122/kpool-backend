<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\ApproveMember;

use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;

interface ApproveMemberInputPort
{
    public function memberIdentifier(): MemberIdentifier;

    public function publishedMemberIdentifier(): ?MemberIdentifier;

    public function principal(): Principal;
}
