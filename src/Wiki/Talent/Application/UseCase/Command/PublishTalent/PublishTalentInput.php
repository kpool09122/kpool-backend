<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\PublishTalent;

use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;

readonly class PublishTalentInput implements PublishTalentInputPort
{
    public function __construct(
        private TalentIdentifier  $talentIdentifier,
        private ?TalentIdentifier $publishedTalentIdentifier,
        private Principal         $principal,
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

    public function principal(): Principal
    {
        return $this->principal;
    }
}
