<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\RejectTalent;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;

readonly class RejectTalentInput implements RejectTalentInputPort
{
    public function __construct(
        private TalentIdentifier    $talentIdentifier,
        private PrincipalIdentifier $principalIdentifier,
    ) {
    }

    public function talentIdentifier(): TalentIdentifier
    {
        return $this->talentIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
