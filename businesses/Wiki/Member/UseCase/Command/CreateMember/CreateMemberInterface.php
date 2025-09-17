<?php

declare(strict_types=1);

namespace Businesses\Wiki\Member\UseCase\Command\CreateMember;

use Businesses\Wiki\Member\Domain\Entity\Member;
use Businesses\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;

interface CreateMemberInterface
{
    /**
     * @param CreateMemberInputPort $input
     * @return Member
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function process(CreateMemberInputPort $input): Member;
}
