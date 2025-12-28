<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\TranslateTalent;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;

readonly class TranslateTalentInput implements TranslateTalentInputPort
{
    public function __construct(
        private TalentIdentifier $talentIdentifier,
        private Principal        $principal,
    ) {
    }

    public function talentIdentifier(): TalentIdentifier
    {
        return $this->talentIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
