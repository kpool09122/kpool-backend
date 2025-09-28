<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\CreateSong;

use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Song\Domain\ValueObject\BelongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;

readonly class CreateSongInput implements CreateSongInputPort
{
    /**
     * @param SongIdentifier|null $publishedSongIdentifier
     * @param EditorIdentifier $editorIdentifier
     * @param Translation $translation
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
        private ?SongIdentifier $publishedSongIdentifier,
        private EditorIdentifier $editorIdentifier,
        private Translation $translation,
        private SongName             $name,
        private array                $belongIdentifiers,
        private Lyricist             $lyricist,
        private Composer             $composer,
        private ?ReleaseDate         $releaseDate,
        private Overview             $overview,
        private ?string              $base64EncodedCoverImage,
        private ?ExternalContentLink $musicVideoLink,
    ) {
    }

    public function publishedSongIdentifier(): ?SongIdentifier
    {
        return $this->publishedSongIdentifier;
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
