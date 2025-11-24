<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Query\GetTalent;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;

interface GetTalentInputPort
{
    public function talentIdentifier(): TalentIdentifier;

    public function language(): Language;
}
