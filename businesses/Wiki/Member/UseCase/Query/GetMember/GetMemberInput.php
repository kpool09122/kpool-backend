<?php

namespace Businesses\Wiki\Member\UseCase\Query\GetMember;

use Businesses\Wiki\Member\Domain\ValueObject\MemberIdentifier;

readonly class GetMemberInput implements GetMemberInputPort
{
    public function __construct(
        private MemberIdentifier $memberIdentifier
    ) {
    }

    public function memberIdentifier(): MemberIdentifier
    {
        return $this->memberIdentifier;
    }
}
