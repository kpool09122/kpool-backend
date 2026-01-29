<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\AutoCreateTalent;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\AutoTalentCreationPayload;

interface AutoCreateTalentInputPort
{
    public function payload(): AutoTalentCreationPayload;

    public function principalIdentifier(): PrincipalIdentifier;
}
