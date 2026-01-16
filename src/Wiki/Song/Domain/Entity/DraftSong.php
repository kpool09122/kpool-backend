<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;

class DraftSong
{
    /**
     * @param SongIdentifier $songIdentifier
     * @param SongIdentifier|null $publishedSongIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param PrincipalIdentifier $editorIdentifier
     * @param Language $language
     * @param SongName $name
     * @param ?AgencyIdentifier $agencyIdentifier
     * @param ?GroupIdentifier $groupIdentifier
     * @param ?TalentIdentifier $talentIdentifier
     * @param Lyricist $lyricist
     * @param Composer $composer
     * @param ReleaseDate|null $releaseDate
     * @param Overview $overView
     * @param ImagePath|null $coverImagePath
     * @param ExternalContentLink|null $musicVideoLink
     * @param ApprovalStatus $status
     * @param PrincipalIdentifier|null $mergerIdentifier
     * @param DateTimeImmutable|null $mergedAt
     */
    public function __construct(
        private readonly SongIdentifier           $songIdentifier,
        private ?SongIdentifier                   $publishedSongIdentifier,
        private readonly TranslationSetIdentifier $translationSetIdentifier,
        private readonly PrincipalIdentifier      $editorIdentifier,
        private readonly Language                 $language,
        private SongName                          $name,
        private string                            $normalizedName,
        private ?AgencyIdentifier                 $agencyIdentifier,
        private ?GroupIdentifier                  $groupIdentifier,
        private ?TalentIdentifier                 $talentIdentifier,
        private Lyricist                          $lyricist,
        private string                            $normalizedLyricist,
        private Composer                          $composer,
        private string                            $normalizedComposer,
        private ?ReleaseDate                      $releaseDate,
        private Overview                          $overView,
        private ?ImagePath                        $coverImagePath,
        private ?ExternalContentLink              $musicVideoLink,
        private ApprovalStatus                    $status,
        private ?PrincipalIdentifier              $mergerIdentifier = null,
        private ?DateTimeImmutable                $mergedAt = null,
    ) {
    }

    public function songIdentifier(): songIdentifier
    {
        return $this->songIdentifier;
    }

    public function publishedSongIdentifier(): ?SongIdentifier
    {
        return $this->publishedSongIdentifier;
    }

    public function setPublishedSongIdentifier(SongIdentifier $songIdentifier): void
    {
        $this->publishedSongIdentifier = $songIdentifier;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    public function editorIdentifier(): PrincipalIdentifier
    {
        return $this->editorIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function name(): SongName
    {
        return $this->name;
    }

    public function normalizedName(): string
    {
        return $this->normalizedName;
    }

    public function setName(SongName $name): void
    {
        $this->name = $name;
    }

    public function setNormalizedName(string $normalizedName): void
    {
        $this->normalizedName = $normalizedName;
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

    public function normalizedLyricist(): string
    {
        return $this->normalizedLyricist;
    }

    public function setLyricist(Lyricist $lyricist): void
    {
        $this->lyricist = $lyricist;
    }

    public function setNormalizedLyricist(string $normalizedLyricist): void
    {
        $this->normalizedLyricist = $normalizedLyricist;
    }

    public function composer(): Composer
    {
        return $this->composer;
    }

    public function normalizedComposer(): string
    {
        return $this->normalizedComposer;
    }

    public function setComposer(Composer $composer): void
    {
        $this->composer = $composer;
    }

    public function setNormalizedComposer(string $normalizedComposer): void
    {
        $this->normalizedComposer = $normalizedComposer;
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

    public function coverImagePath(): ?ImagePath
    {
        return $this->coverImagePath;
    }

    public function setCoverImagePath(ImagePath $coverImagePath): void
    {
        $this->coverImagePath = $coverImagePath;
    }

    public function musicVideoLink(): ?ExternalContentLink
    {
        return $this->musicVideoLink;
    }

    public function setMusicVideoLink(ExternalContentLink $musicVideoLink): void
    {
        $this->musicVideoLink = $musicVideoLink;
    }

    public function status(): ApprovalStatus
    {
        return $this->status;
    }

    public function setStatus(ApprovalStatus $status): void
    {
        $this->status = $status;
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
}
