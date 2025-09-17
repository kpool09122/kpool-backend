<?php

namespace Businesses\Wiki\Member\UseCase\Query\GetMember;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Member\Domain\ValueObject\MemberIdentifier;

interface GetMemberInputPort
{
    public function memberIdentifier(): MemberIdentifier;

    public function translation(): Translation;
}
