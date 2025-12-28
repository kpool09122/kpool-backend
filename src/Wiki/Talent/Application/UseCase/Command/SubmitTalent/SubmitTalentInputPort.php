<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\SubmitTalent;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;

interface SubmitTalentInputPort
{
    public function talentIdentifier(): TalentIdentifier;

    public function principal(): Principal;
}
