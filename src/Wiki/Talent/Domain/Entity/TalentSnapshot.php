<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Talent\Domain\ValueObject\TalentSnapshotIdentifier;

readonly class TalentSnapshot
{
    /**
     * @param TalentSnapshotIdentifier $snapshotIdentifier
     * @param TalentIdentifier $talentIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Language $language
     * @param TalentName $name
     * @param RealName $realName
     * @param ?AgencyIdentifier $agencyIdentifier
     * @param list<GroupIdentifier> $groupIdentifiers
     * @param Birthday|null $birthday
     * @param Career $career
     * @param ImagePath|null $imageLink
     * @param RelevantVideoLinks $relevantVideoLinks
     * @param Version $version
     * @param DateTimeImmutable $createdAt
     */
    public function __construct(
        private TalentSnapshotIdentifier  $snapshotIdentifier,
        private TalentIdentifier          $talentIdentifier,
        private TranslationSetIdentifier  $translationSetIdentifier,
        private Language                  $language,
        private TalentName                $name,
        private RealName                  $realName,
        private ?AgencyIdentifier         $agencyIdentifier,
        private array                     $groupIdentifiers,
        private ?Birthday                 $birthday,
        private Career                    $career,
        private ?ImagePath                $imageLink,
        private RelevantVideoLinks        $relevantVideoLinks,
        private Version                   $version,
        private DateTimeImmutable         $createdAt,
    ) {
    }

    public function snapshotIdentifier(): TalentSnapshotIdentifier
    {
        return $this->snapshotIdentifier;
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

    public function imageLink(): ?ImagePath
    {
        return $this->imageLink;
    }

    public function relevantVideoLinks(): RelevantVideoLinks
    {
        return $this->relevantVideoLinks;
    }

    public function version(): Version
    {
        return $this->version;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
