<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\MergeTalent;

use DateTimeImmutable;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\Birthday;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\RealName;

interface MergeTalentInputPort
{
    public function talentIdentifier(): TalentIdentifier;

    public function name(): TalentName;

    public function realName(): RealName;

    public function agencyIdentifier(): ?AgencyIdentifier;

    /**
     * @return GroupIdentifier[]
     */
    public function groupIdentifiers(): array;

    public function birthday(): ?Birthday;

    public function career(): Career;

    public function principalIdentifier(): PrincipalIdentifier;

    public function mergedAt(): DateTimeImmutable;
}
