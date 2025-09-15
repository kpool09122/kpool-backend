<?php

namespace Businesses\Member\UseCase\Query\GetMember;

use Businesses\Member\Domain\ValueObject\MemberIdentifier;

class GetMemberInput implements GetMemberInputPort
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
