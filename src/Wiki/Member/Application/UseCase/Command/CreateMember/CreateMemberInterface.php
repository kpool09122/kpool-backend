<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\CreateMember;

use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;

interface CreateMemberInterface
{
    /**
     * @param CreateMemberInputPort $input
     * @return DraftMember
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function process(CreateMemberInputPort $input): DraftMember;
}
