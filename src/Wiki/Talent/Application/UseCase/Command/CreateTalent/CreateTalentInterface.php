<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\CreateTalent;

use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;

interface CreateTalentInterface
{
    /**
     * @param CreateTalentInputPort $input
     * @return DraftTalent
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     */
    public function process(CreateTalentInputPort $input): DraftTalent;
}
