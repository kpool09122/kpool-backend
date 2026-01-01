<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\ApproveTalent;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;

interface ApproveTalentInputPort
{
    public function talentIdentifier(): TalentIdentifier;

    public function publishedTalentIdentifier(): ?TalentIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
