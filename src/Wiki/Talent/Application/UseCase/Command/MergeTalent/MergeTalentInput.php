<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\MergeTalent;

use DateTimeImmutable;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

readonly class MergeTalentInput implements MergeTalentInputPort
{
    /**
     * @param TalentIdentifier $talentIdentifier
     * @param TalentName $name
     * @param RealName $realName
     * @param ?AgencyIdentifier $agencyIdentifier
     * @param GroupIdentifier[] $groupIdentifiers
     * @param Birthday|null $birthday
     * @param Career $career
     * @param RelevantVideoLinks $relevantVideoLinks
     * @param PrincipalIdentifier $principalIdentifier
     * @param DateTimeImmutable $mergedAt
     */
    public function __construct(
        private TalentIdentifier    $talentIdentifier,
        private TalentName          $name,
        private RealName            $realName,
        private ?AgencyIdentifier   $agencyIdentifier,
        private array               $groupIdentifiers,
        private ?Birthday           $birthday,
        private Career              $career,
        private RelevantVideoLinks  $relevantVideoLinks,
        private PrincipalIdentifier $principalIdentifier,
        private DateTimeImmutable   $mergedAt,
    ) {
    }

    public function talentIdentifier(): TalentIdentifier
    {
        return $this->talentIdentifier;
    }

    public function name(): TalentName
    {
        return $this->name;
    }

    public function realName(): RealName
    {
        return $this->realName;
    }

    public function agencyIdentifier(): ?AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    /**
     * @return GroupIdentifier[]
     */
    public function groupIdentifiers(): array
    {
        return $this->groupIdentifiers;
    }

    public function birthday(): ?Birthday
    {
        return $this->birthday;
    }

    public function career(): Career
    {
        return $this->career;
    }

    public function relevantVideoLinks(): RelevantVideoLinks
    {
        return $this->relevantVideoLinks;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function mergedAt(): DateTimeImmutable
    {
        return $this->mergedAt;
    }
}
