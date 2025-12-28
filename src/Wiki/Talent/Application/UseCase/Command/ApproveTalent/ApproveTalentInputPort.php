<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\ApproveTalent;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;

interface ApproveTalentInputPort
{
    public function talentIdentifier(): TalentIdentifier;

    public function publishedTalentIdentifier(): ?TalentIdentifier;

    public function principal(): Principal;
}
