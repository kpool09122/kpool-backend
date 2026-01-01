<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Query\GetTalent;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;

readonly class GetTalentInput implements GetTalentInputPort
{
    public function __construct(
        private TalentIdentifier $talentIdentifier,
        private Language         $language,
    ) {
    }

    public function talentIdentifier(): TalentIdentifier
    {
        return $this->talentIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
    }
}
