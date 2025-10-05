<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\PublishMember;

use Source\Wiki\Member\Application\Exception\ExistsApprovedButNotTranslatedMemberException;
use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Domain\Entity\Member;
use Source\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

interface PublishMemberInterface
{
    /**
     * @param PublishMemberInputPort $input
     * @return Member
     * @throws MemberNotFoundException
     * @throws InvalidStatusException
     * @throws ExistsApprovedButNotTranslatedMemberException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     */
    public function process(PublishMemberInputPort $input): Member;
}
