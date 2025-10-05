<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Query\GetTalent;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;

readonly class GetTalentInput implements GetTalentInputPort
{
    public function __construct(
        private TalentIdentifier $talentIdentifier,
        private Translation      $translation,
    ) {
    }

    public function talentIdentifier(): TalentIdentifier
    {
        return $this->talentIdentifier;
    }

    public function translation(): Translation
    {
        return $this->translation;
    }
}
