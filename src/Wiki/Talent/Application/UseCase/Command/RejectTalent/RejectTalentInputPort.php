<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\RejectTalent;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;

interface RejectTalentInputPort
{
    public function talentIdentifier(): TalentIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
