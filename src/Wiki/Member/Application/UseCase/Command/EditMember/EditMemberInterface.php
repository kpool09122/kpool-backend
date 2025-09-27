<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\EditMember;

use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Domain\Entity\DraftMember;

interface EditMemberInterface
{
    /**
     * @param EditMemberInputPort $input
     * @return DraftMember
     * @throws MemberNotFoundException
     */
    public function process(EditMemberInputPort $input): DraftMember;
}
