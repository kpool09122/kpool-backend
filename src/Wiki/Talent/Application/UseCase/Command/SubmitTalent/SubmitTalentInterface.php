<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\SubmitTalent;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;

interface SubmitTalentInterface
{
    /**
     * @param SubmitTalentInputPort $input
     * @return DraftTalent
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(SubmitTalentInputPort $input): DraftTalent;
}
