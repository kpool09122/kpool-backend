<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\SubmitMember;

use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;

interface SubmitMemberInputPort
{
    public function memberIdentifier(): MemberIdentifier;

    public function principal(): Principal;
}
