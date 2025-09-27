<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\RejectUpdatedMember;

use Source\Wiki\Group\Application\Exception\ExistsApprovedButNotTranslatedGroupException;
use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;

interface RejectUpdatedMemberInterface
{
    /**
     * @param RejectUpdatedMemberInputPort $input
     * @return DraftMember
     * @throws MemberNotFoundException
     * @throws ExistsApprovedButNotTranslatedGroupException
     * @throws InvalidStatusException
     */
    public function process(RejectUpdatedMemberInputPort $input): DraftMember;
}
