<?php

namespace Businesses\Wiki\Member\UseCase\Command\EditMember;

use Businesses\Wiki\Member\Domain\Entity\Member;
use Businesses\Wiki\Member\UseCase\Exception\MemberNotFoundException;

interface EditMemberInterface
{
    /**
     * @param EditMemberInputPort $input
     * @return ?Member
     * @throws MemberNotFoundException
     */
    public function process(EditMemberInputPort $input): ?Member;
}
