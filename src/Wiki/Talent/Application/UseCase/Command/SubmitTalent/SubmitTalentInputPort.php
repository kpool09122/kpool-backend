<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\SubmitTalent;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;

interface SubmitTalentInputPort
{
    public function talentIdentifier(): TalentIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
