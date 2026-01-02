<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\RollbackTalent;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;

readonly class RollbackTalentInput implements RollbackTalentInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private TalentIdentifier $talentIdentifier,
        private Version $targetVersion,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function talentIdentifier(): TalentIdentifier
    {
        return $this->talentIdentifier;
    }

    public function targetVersion(): Version
    {
        return $this->targetVersion;
    }
}
