<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\ApproveTalent;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;

readonly class ApproveTalentInput implements ApproveTalentInputPort
{
    public function __construct(
        private TalentIdentifier    $talentIdentifier,
        private ?TalentIdentifier   $publishedTalentIdentifier,
        private PrincipalIdentifier $principalIdentifier,
    ) {
    }

    public function talentIdentifier(): TalentIdentifier
    {
        return $this->talentIdentifier;
    }

    public function publishedTalentIdentifier(): ?TalentIdentifier
    {
        return $this->publishedTalentIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
