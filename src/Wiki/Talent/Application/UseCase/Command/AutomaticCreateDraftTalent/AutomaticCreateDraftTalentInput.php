<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\AutomaticCreateDraftTalent;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\AutomaticDraftTalentCreationPayload;

readonly class AutomaticCreateDraftTalentInput implements AutomaticCreateDraftTalentInputPort
{
    public function __construct(
        private AutomaticDraftTalentCreationPayload $payload,
        private PrincipalIdentifier                 $principalIdentifier,
    ) {
    }

    public function payload(): AutomaticDraftTalentCreationPayload
    {
        return $this->payload;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
