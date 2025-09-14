<?php

namespace Businesses\Member\UseCase\Query\GetMember;

use Businesses\Member\Domain\ValueObject\MemberIdentifier;

interface GetMemberInputPort
{
    public function memberIdentifier(): MemberIdentifier;
}
