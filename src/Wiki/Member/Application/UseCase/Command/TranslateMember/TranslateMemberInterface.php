<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\TranslateMember;

use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Domain\Entity\DraftMember;

interface TranslateMemberInterface
{
    /**
     * @param TranslateMemberInputPort $input
     * @return DraftMember[]
     * @throws MemberNotFoundException
     */
    public function process(TranslateMemberInputPort $input): array;
}
