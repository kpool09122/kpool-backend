<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Composer;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Lyricist;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\ReleaseDate;

class Song
{
    /**
     * @param SongIdentifier $songIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Slug $slug
     * @param Language $language
     * @param SongName $name
     * @param ?AgencyIdentifier $agencyIdentifier
     * @param ?GroupIdentifier $groupIdentifier
     * @param ?TalentIdentifier $talentIdentifier
     * @param Lyricist $lyricist
     * @param Composer $composer
     * @param ReleaseDate|null $releaseDate
     * @param Overview $overView
     * @param Version $version
     * @param PrincipalIdentifier|null $mergerIdentifier
     * @param DateTimeImmutable|null $mergedAt
     * @param PrincipalIdentifier|null $editorIdentifier
     * @param PrincipalIdentifier|null $approverIdentifier
     * @param bool $isOfficial
     * @param AccountIdentifier|null $ownerAccountIdentifier
     */
    public function __construct(
        private readonly SongIdentifier           $songIdentifier,
        private readonly TranslationSetIdentifier $translationSetIdentifier,
        private readonly Slug                     $slug,
        private readonly Language                 $language,
        private SongName                          $name,
        private ?AgencyIdentifier                 $agencyIdentifier,
        private ?GroupIdentifier                  $groupIdentifier,
        private ?TalentIdentifier                 $talentIdentifier,
        private Lyricist                          $lyricist,
        private Composer                          $composer,
        private ?ReleaseDate                      $releaseDate,
        private Overview                          $overView,
        private Version                           $version,
        private ?PrincipalIdentifier              $mergerIdentifier = null,
        private ?DateTimeImmutable                $mergedAt = null,
        private ?PrincipalIdentifier              $editorIdentifier = null,
        private ?PrincipalIdentifier              $approverIdentifier = null,
        private bool                              $isOfficial = false,
        private ?AccountIdentifier                $ownerAccountIdentifier = null,
        private ?PrincipalIdentifier              $sourceEditorIdentifier = null,
        private ?DateTimeImmutable                $translatedAt = null,
        private ?DateTimeImmutable                $approvedAt = null,
    ) {
    }

    public function songIdentifier(): songIdentifier
    {
        return $this->songIdentifier;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    public function slug(): Slug
    {
        return $this->slug;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function name(): SongName
    {
        return $this->name;
    }

    public function setName(SongName $name): void
    {
        $this->name = $name;
    }

    public function agencyIdentifier(): ?AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function setAgencyIdentifier(AgencyIdentifier $agencyIdentifier): void
    {
        $this->agencyIdentifier = $agencyIdentifier;
    }

    public function groupIdentifier(): ?GroupIdentifier
    {
        return $this->groupIdentifier;
    }

    public function setGroupIdentifier(GroupIdentifier $groupIdentifier): void
    {
        $this->groupIdentifier = $groupIdentifier;
    }

    public function talentIdentifier(): ?TalentIdentifier
    {
        return $this->talentIdentifier;
    }

    public function setTalentIdentifier(TalentIdentifier $talentIdentifier): void
    {
        $this->talentIdentifier = $talentIdentifier;
    }

    public function lyricist(): Lyricist
    {
        return $this->lyricist;
    }

    public function setLyricist(Lyricist $lyricist): void
    {
        $this->lyricist = $lyricist;
    }

    public function composer(): Composer
    {
        return $this->composer;
    }

    public function setComposer(Composer $composer): void
    {
        $this->composer = $composer;
    }

    public function releaseDate(): ?ReleaseDate
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(ReleaseDate $releaseDate): void
    {
        $this->releaseDate = $releaseDate;
    }

    public function overView(): Overview
    {
        return $this->overView;
    }

    public function setOverView(Overview $overView): void
    {
        $this->overView = $overView;
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

    public function editorIdentifier(): ?PrincipalIdentifier
    {
        return $this->editorIdentifier;
    }

    public function setEditorIdentifier(?PrincipalIdentifier $editorIdentifier): void
    {
        $this->editorIdentifier = $editorIdentifier;
    }

    public function approverIdentifier(): ?PrincipalIdentifier
    {
        return $this->approverIdentifier;
    }

    public function setApproverIdentifier(?PrincipalIdentifier $approverIdentifier): void
    {
        $this->approverIdentifier = $approverIdentifier;
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

    public function sourceEditorIdentifier(): ?PrincipalIdentifier
    {
        return $this->sourceEditorIdentifier;
    }

    public function setSourceEditorIdentifier(?PrincipalIdentifier $sourceEditorIdentifier): void
    {
        $this->sourceEditorIdentifier = $sourceEditorIdentifier;
    }

    public function translatedAt(): ?DateTimeImmutable
    {
        return $this->translatedAt;
    }

    public function setTranslatedAt(?DateTimeImmutable $translatedAt): void
    {
        $this->translatedAt = $translatedAt;
    }

    public function approvedAt(): ?DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?DateTimeImmutable $approvedAt): void
    {
        $this->approvedAt = $approvedAt;
    }
}
