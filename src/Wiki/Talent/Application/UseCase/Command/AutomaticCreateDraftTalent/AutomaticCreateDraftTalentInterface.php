<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\AutomaticCreateDraftTalent;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;

interface AutomaticCreateDraftTalentInterface
{
    /**
     * @param AutomaticCreateDraftTalentInputPort $input
     * @return DraftTalent
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(AutomaticCreateDraftTalentInputPort $input): DraftTalent;
}
