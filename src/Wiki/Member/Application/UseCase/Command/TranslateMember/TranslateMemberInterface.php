<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\TranslateMember;

use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

interface TranslateMemberInterface
{
    /**
     * @param TranslateMemberInputPort $input
     * @return DraftMember[]
     * @throws MemberNotFoundException
     * @throws UnauthorizedException
     */
    public function process(TranslateMemberInputPort $input): array;
}
