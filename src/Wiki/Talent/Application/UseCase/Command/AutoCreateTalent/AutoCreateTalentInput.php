<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\AutoCreateTalent;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\AutoTalentCreationPayload;

readonly class AutoCreateTalentInput implements AutoCreateTalentInputPort
{
    public function __construct(
        private AutoTalentCreationPayload $payload,
        private PrincipalIdentifier       $principalIdentifier,
    ) {
    }

    public function payload(): AutoTalentCreationPayload
    {
        return $this->payload;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
