<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\CreateTalent;

use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;

interface CreateTalentInterface
{
    /**
     * @param CreateTalentInputPort $input
     * @return DraftTalent
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(CreateTalentInputPort $input): DraftTalent;
}
