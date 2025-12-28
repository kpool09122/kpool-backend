<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\RejectTalent;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;

interface RejectTalentInputPort
{
    public function talentIdentifier(): TalentIdentifier;

    public function principal(): Principal;
}
