<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

final readonly class AutomaticDraftSongCreationPayload
{
    /**
     * @param BelongIdentifier[] $belongIdentifiers
     */
    public function __construct(
        private EditorIdentifier $editorIdentifier,
        private Translation $translation,
        private SongName $name,
        private ?AgencyIdentifier $agencyIdentifier,
        private array $belongIdentifiers,
        private Lyricist $lyricist,
        private Composer $composer,
        private ?ReleaseDate $releaseDate,
        private Overview $overview,
        private AutomaticDraftSongSource $source,
    ) {
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

    public function agencyIdentifier(): ?AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    /**
     * @return BelongIdentifier[]
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

    public function source(): AutomaticDraftSongSource
    {
        return $this->source;
    }
}
