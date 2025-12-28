<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\AutomaticCreateDraftTalent;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Talent\Domain\ValueObject\AutomaticDraftTalentCreationPayload;

readonly class AutomaticCreateDraftTalentInput implements AutomaticCreateDraftTalentInputPort
{
    public function __construct(
        private AutomaticDraftTalentCreationPayload $payload,
        private Principal $principal,
    ) {
    }

    public function payload(): AutomaticDraftTalentCreationPayload
    {
        return $this->payload;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
