<?php

namespace Businesses\Member\UseCase\Command\EditMember;

use Businesses\Member\Domain\Entity\Member;
use Businesses\Member\UseCase\Exception\MemberNotFoundException;

interface EditMemberInterface
{
    /**
     * @param EditMemberInputPort $input
     * @return ?Member
     * @throws MemberNotFoundException
     */
    public function process(EditMemberInputPort $input): ?Member;
}
