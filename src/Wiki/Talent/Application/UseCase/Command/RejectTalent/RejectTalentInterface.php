<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\RejectTalent;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;

interface RejectTalentInterface
{
    /**
     * @param RejectTalentInputPort $input
     * @return DraftTalent
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function process(RejectTalentInputPort $input): DraftTalent;
}
