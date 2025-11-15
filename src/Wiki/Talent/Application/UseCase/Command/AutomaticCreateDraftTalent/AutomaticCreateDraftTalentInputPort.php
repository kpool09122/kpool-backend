<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\AutomaticCreateDraftTalent;

use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Talent\Domain\ValueObject\AutomaticDraftTalentCreationPayload;

interface AutomaticCreateDraftTalentInputPort
{
    public function payload(): AutomaticDraftTalentCreationPayload;

    public function principal(): Principal;
}
