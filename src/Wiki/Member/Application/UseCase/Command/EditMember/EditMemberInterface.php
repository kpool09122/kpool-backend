<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\EditMember;

use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

interface EditMemberInterface
{
    /**
     * @param EditMemberInputPort $input
     * @return DraftMember
     * @throws MemberNotFoundException
     * @throws UnauthorizedException
     */
    public function process(EditMemberInputPort $input): DraftMember;
}
