<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\AutomaticCreateDraftTalent;

use Source\Wiki\Talent\Domain\Entity\DraftTalent;

interface AutomaticCreateDraftTalentInterface
{
    public function process(AutomaticCreateDraftTalentInputPort $input): DraftTalent;
}
