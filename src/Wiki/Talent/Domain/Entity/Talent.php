<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

class Talent
{
    /**
     * @param TalentIdentifier $talentIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Language $language
     * @param TalentName $name
     * @param RealName $realName
     * @param ?AgencyIdentifier $agencyIdentifier
     * @param GroupIdentifier[] $groupIdentifiers
     * @param Birthday|null $birthday
     * @param Career $career
     * @param RelevantVideoLinks $relevantVideoLinks
     * @param Version $version
     * @param PrincipalIdentifier|null $mergerIdentifier
     * @param DateTimeImmutable|null $mergedAt
     * @param bool $isOfficial
     * @param AccountIdentifier|null $ownerAccountIdentifier
     */
    public function __construct(
        private readonly TalentIdentifier         $talentIdentifier,
        private readonly TranslationSetIdentifier $translationSetIdentifier,
        private readonly Language                 $language,
        private TalentName                        $name,
        private RealName                          $realName,
        private ?AgencyIdentifier                 $agencyIdentifier,
        private array                             $groupIdentifiers,
        private ?Birthday                         $birthday,
        private Career                            $career,
        private RelevantVideoLinks                $relevantVideoLinks,
        private Version                           $version,
        private ?PrincipalIdentifier              $mergerIdentifier = null,
        private ?DateTimeImmutable                $mergedAt = null,
        private bool                              $isOfficial = false,
        private ?AccountIdentifier                $ownerAccountIdentifier = null,
    ) {
    }

    public function talentIdentifier(): TalentIdentifier
    {
        return $this->talentIdentifier;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function name(): TalentName
    {
        return $this->name;
    }

    public function setName(TalentName $name): void
    {
        $this->name = $name;
    }

    public function realName(): RealName
    {
        return $this->realName;
    }

    public function setRealName(RealName $realName): void
    {
        $this->realName = $realName;
    }

    public function agencyIdentifier(): ?AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function setAgencyIdentifier(AgencyIdentifier $agencyIdentifier): void
    {
        $this->agencyIdentifier = $agencyIdentifier;
    }

    /**
     * @return GroupIdentifier[]
     */
    public function groupIdentifiers(): array
    {
        return $this->groupIdentifiers;
    }

    /**
     * @param GroupIdentifier[] $groupIdentifiers
     * @return void
     */
    public function setGroupIdentifiers(array $groupIdentifiers): void
    {
        $this->groupIdentifiers = $groupIdentifiers;
    }

    public function birthday(): ?Birthday
    {
        return $this->birthday;
    }

    public function setBirthday(?Birthday $birthday): void
    {
        $this->birthday = $birthday;
    }

    public function career(): Career
    {
        return $this->career;
    }

    public function setCareer(Career $career): void
    {
        $this->career = $career;
    }

    /**
     * @return RelevantVideoLinks
     */
    public function relevantVideoLinks(): RelevantVideoLinks
    {
        return $this->relevantVideoLinks;
    }

    /**
     * @param RelevantVideoLinks $relevantVideoLinks
     * @return void
     */
    public function setRelevantVideoLinks(RelevantVideoLinks $relevantVideoLinks): void
    {
        $this->relevantVideoLinks = $relevantVideoLinks;
    }

    public function version(): Version
    {
        return $this->version;
    }

    public function updateVersion(): void
    {
        $this->version = Version::nextVersion($this->version);
    }

    public function hasSameVersion(Version $version): bool
    {
        return $this->version->value() === $version->value();
    }

    public function isVersionGreaterThan(Version $version): bool
    {
        return $this->version->value() > $version->value();
    }

    public function mergerIdentifier(): ?PrincipalIdentifier
    {
        return $this->mergerIdentifier;
    }

    public function setMergerIdentifier(?PrincipalIdentifier $mergerIdentifier): void
    {
        $this->mergerIdentifier = $mergerIdentifier;
    }

    public function mergedAt(): ?DateTimeImmutable
    {
        return $this->mergedAt;
    }

    public function setMergedAt(?DateTimeImmutable $mergedAt): void
    {
        $this->mergedAt = $mergedAt;
    }

    public function isOfficial(): bool
    {
        return $this->isOfficial;
    }

    public function ownerAccountIdentifier(): ?AccountIdentifier
    {
        return $this->ownerAccountIdentifier;
    }

    public function markOfficial(AccountIdentifier $ownerAccountIdentifier): void
    {
        if ($this->isOfficial) {
            return;
        }

        $this->isOfficial = true;
        $this->ownerAccountIdentifier = $ownerAccountIdentifier;
    }
}
