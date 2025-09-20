<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\EditMember;

use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Domain\Entity\Member;

interface EditMemberInterface
{
    /**
     * @param EditMemberInputPort $input
     * @return Member
     * @throws MemberNotFoundException
     */
    public function process(EditMemberInputPort $input): Member;
}
