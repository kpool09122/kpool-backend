<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Command\CreateMember;

use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

interface CreateMemberInterface
{
    /**
     * @param CreateMemberInputPort $input
     * @return DraftMember
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     */
    public function process(CreateMemberInputPort $input): DraftMember;
}
