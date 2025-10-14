<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Entity;

use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\BelongIdentifier;
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
     * @param EditorIdentifier $editorIdentifier
     * @param Translation $translation
     * @param SongName $name
     * @param ?AgencyIdentifier $agencyIdentifier
     * @param BelongIdentifier[] $belongIdentifiers // タレントIDか、グループIDを想定
     * @param Lyricist $lyricist
     * @param Composer $composer
     * @param ReleaseDate|null $releaseDate
     * @param Overview $overView
     * @param ImagePath|null $coverImagePath
     * @param ExternalContentLink|null $musicVideoLink
     * @param ApprovalStatus $status
     */
    public function __construct(
        private readonly SongIdentifier $songIdentifier,
        private ?SongIdentifier $publishedSongIdentifier,
        private readonly TranslationSetIdentifier $translationSetIdentifier,
        private EditorIdentifier $editorIdentifier,
        private readonly Translation $translation,
        private SongName $name,
        private ?AgencyIdentifier $agencyIdentifier,
        private array $belongIdentifiers,
        private Lyricist $lyricist,
        private Composer $composer,
        private ?ReleaseDate $releaseDate,
        private Overview $overView,
        private ?ImagePath $coverImagePath,
        private ?ExternalContentLink $musicVideoLink,
        private ApprovalStatus $status,
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

    public function editorIdentifier(): EditorIdentifier
    {
        return $this->editorIdentifier;
    }

    public function translation(): Translation
    {
        return $this->translation;
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

    /**
     * @return BelongIdentifier[]
     */
    public function belongIdentifiers(): array
    {
        return $this->belongIdentifiers;
    }

    /**
     * @param BelongIdentifier[] $belongIdentifiers
     * @return void
     */
    public function setBelongIdentifiers(array $belongIdentifiers): void
    {
        $this->belongIdentifiers = $belongIdentifiers;
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
}
