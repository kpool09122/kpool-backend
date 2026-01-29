<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\AutoCreateTalent;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;

interface AutoCreateTalentInterface
{
    /**
     * @param AutoCreateTalentInputPort $input
     * @return DraftTalent
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(AutoCreateTalentInputPort $input): DraftTalent;
}
