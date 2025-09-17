<?php

declare(strict_types=1);

namespace Businesses\Wiki\Song\UseCase\Command\EditSong;

use Businesses\Shared\ValueObject\ExternalContentLink;
use Businesses\Wiki\Song\Domain\ValueObject\BelongIdentifier;
use Businesses\Wiki\Song\Domain\ValueObject\Composer;
use Businesses\Wiki\Song\Domain\ValueObject\Lyricist;
use Businesses\Wiki\Song\Domain\ValueObject\Overview;
use Businesses\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Businesses\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Businesses\Wiki\Song\Domain\ValueObject\SongName;

readonly class EditSongInput implements EditSongInputPort
{
    /**
     * @param SongIdentifier $songIdentifier
     * @param SongName $name
     * @param list<BelongIdentifier> $belongIdentifiers
     * @param Lyricist $lyricist
     * @param Composer $composer
     * @param ?ReleaseDate $releaseDate
     * @param Overview $overview
     * @param ?string $base64EncodedCoverImage
     * @param ?ExternalContentLink $musicVideoLink
     */
    public function __construct(
        private SongIdentifier $songIdentifier,
        private SongName $name,
        private array $belongIdentifiers,
        private Lyricist $lyricist,
        private Composer $composer,
        private ?ReleaseDate $releaseDate,
        private Overview $overview,
        private ?string $base64EncodedCoverImage,
        private ?ExternalContentLink $musicVideoLink,
    ) {
    }

    public function songIdentifier(): SongIdentifier
    {
        return $this->songIdentifier;
    }

    public function name(): SongName
    {
        return $this->name;
    }

    /**
     * @return list<BelongIdentifier>
     */
    public function belongIdentifiers(): array
    {
        return $this->belongIdentifiers;
    }

    public function lyricist(): Lyricist
    {
        return $this->lyricist;
    }

    public function composer(): Composer
    {
        return $this->composer;
    }

    public function releaseDate(): ?ReleaseDate
    {
        return $this->releaseDate;
    }

    public function overview(): Overview
    {
        return $this->overview;
    }

    public function base64EncodedCoverImage(): ?string
    {
        return $this->base64EncodedCoverImage;
    }

    public function musicVideoLink(): ?ExternalContentLink
    {
        return $this->musicVideoLink;
    }
}
