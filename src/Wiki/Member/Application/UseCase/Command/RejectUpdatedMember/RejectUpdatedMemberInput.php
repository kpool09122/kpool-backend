<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\RejectUpdatedMember;

use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;

readonly class RejectUpdatedMemberInput implements RejectUpdatedMemberInputPort
{
    public function __construct(
        private MemberIdentifier  $memberIdentifier,
    ) {
    }

    public function memberIdentifier(): MemberIdentifier
    {
        return $this->memberIdentifier;
    }
}
