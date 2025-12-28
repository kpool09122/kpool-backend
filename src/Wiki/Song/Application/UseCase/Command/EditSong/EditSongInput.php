<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\EditSong;

use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\BelongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;

readonly class EditSongInput implements EditSongInputPort
{
    /**
     * @param SongIdentifier $songIdentifier
     * @param SongName $name
     * @param ?AgencyIdentifier $agencyIdentifier
     * @param list<BelongIdentifier> $belongIdentifiers
     * @param Lyricist $lyricist
     * @param Composer $composer
     * @param ?ReleaseDate $releaseDate
     * @param Overview $overview
     * @param ?string $base64EncodedCoverImage
     * @param ?ExternalContentLink $musicVideoLink
     * @param Principal $principal
     */
    public function __construct(
        private SongIdentifier $songIdentifier,
        private SongName $name,
        private ?AgencyIdentifier $agencyIdentifier,
        private array $belongIdentifiers,
        private Lyricist $lyricist,
        private Composer $composer,
        private ?ReleaseDate $releaseDate,
        private Overview $overview,
        private ?string $base64EncodedCoverImage,
        private ?ExternalContentLink $musicVideoLink,
        private Principal $principal,
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

    public function agencyIdentifier(): ?AgencyIdentifier
    {
        return $this->agencyIdentifier;
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

    public function principal(): Principal
    {
        return $this->principal;
    }
}
