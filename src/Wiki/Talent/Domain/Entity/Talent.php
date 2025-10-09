<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Entity;

use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

class Talent
{
    /**
     * @param TalentIdentifier $talentIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Translation $translation
     * @param TalentName $name
     * @param RealName $realName
     * @param ?AgencyIdentifier $agencyIdentifier
     * @param GroupIdentifier[] $groupIdentifiers
     * @param Birthday|null $birthday
     * @param Career $career
     * @param ImagePath|null $imageLink
     * @param RelevantVideoLinks $relevantVideoLinks
     */
    public function __construct(
        private readonly TalentIdentifier         $talentIdentifier,
        private readonly TranslationSetIdentifier $translationSetIdentifier,
        private readonly Translation              $translation,
        private TalentName                        $name,
        private RealName                          $realName,
        private ?AgencyIdentifier $agencyIdentifier,
        private array                             $groupIdentifiers,
        private ?Birthday                         $birthday,
        private Career                            $career,
        private ?ImagePath                        $imageLink,
        private RelevantVideoLinks                $relevantVideoLinks,
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

    public function translation(): Translation
    {
        return $this->translation;
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

    public function imageLink(): ?ImagePath
    {
        return $this->imageLink;
    }

    public function setImageLink(?ImagePath $imageLink): void
    {
        $this->imageLink = $imageLink;
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
}
