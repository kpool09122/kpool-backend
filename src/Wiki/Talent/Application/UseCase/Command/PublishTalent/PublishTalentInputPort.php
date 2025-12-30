<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\PublishTalent;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;

interface PublishTalentInputPort
{
    public function talentIdentifier(): TalentIdentifier;

    public function publishedTalentIdentifier(): ?TalentIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
