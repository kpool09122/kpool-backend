<?php

namespace Businesses\Wiki\Song\Domain\Entity;

use Businesses\Shared\ValueObject\ExternalContentLink;
use Businesses\Shared\ValueObject\ImagePath;
use Businesses\Wiki\Song\Domain\ValueObject\BelongIdentifier;
use Businesses\Wiki\Song\Domain\ValueObject\Composer;
use Businesses\Wiki\Song\Domain\ValueObject\Lyricist;
use Businesses\Wiki\Song\Domain\ValueObject\Overview;
use Businesses\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Businesses\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Businesses\Wiki\Song\Domain\ValueObject\SongName;

class Song
{
    /**
     * @param SongIdentifier $songIdentifier
     * @param SongName $name
     * @param list<BelongIdentifier> $belongIdentifiers
     * @param Lyricist $lyricist
     * @param Composer $composer
     * @param Overview $overView
     * @param ImagePath|null $coverImagePath
     * @param ?ExternalContentLink $musicVideoLink
     */
    public function __construct(
        private readonly SongIdentifier $songIdentifier,
        private SongName                $name,
        private array                   $belongIdentifiers,
        private Lyricist                $lyricist,
        private Composer                $composer,
        private ?ReleaseDate            $releaseDate,
        private Overview                $overView,
        private ?ImagePath              $coverImagePath,
        private ?ExternalContentLink    $musicVideoLink,
    ) {
    }

    public function songIdentifier(): songIdentifier
    {
        return $this->songIdentifier;
    }

    public function name(): SongName
    {
        return $this->name;
    }

    public function setName(SongName $name): void
    {
        $this->name = $name;
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
}
