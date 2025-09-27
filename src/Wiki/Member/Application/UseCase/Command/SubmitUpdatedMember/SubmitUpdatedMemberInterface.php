<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\SubmitUpdatedMember;

use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;

interface SubmitUpdatedMemberInterface
{
    /**
     * @param SubmitUpdatedMemberInputPort $input
     * @return DraftMember
     * @throws MemberNotFoundException
     * @throws InvalidStatusException
     */
    public function process(SubmitUpdatedMemberInputPort $input): DraftMember;
}
