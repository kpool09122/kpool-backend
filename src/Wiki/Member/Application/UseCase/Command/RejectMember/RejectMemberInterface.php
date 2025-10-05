<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\RejectMember;

use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

interface RejectMemberInterface
{
    /**
     * @param RejectMemberInputPort $input
     * @return DraftMember
     * @throws MemberNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function process(RejectMemberInputPort $input): DraftMember;
}
