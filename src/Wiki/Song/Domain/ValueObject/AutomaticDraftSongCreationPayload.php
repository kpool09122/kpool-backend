<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

final readonly class AutomaticDraftSongCreationPayload
{
    /**
     * @param BelongIdentifier[] $belongIdentifiers
     */
    public function __construct(
        private PrincipalIdentifier      $editorIdentifier,
        private Language                 $language,
        private SongName                 $name,
        private ?AgencyIdentifier        $agencyIdentifier,
        private array                    $belongIdentifiers,
        private Lyricist                 $lyricist,
        private Composer                 $composer,
        private ?ReleaseDate             $releaseDate,
        private Overview                 $overview,
        private AutomaticDraftSongSource $source,
    ) {
    }

    public function editorIdentifier(): PrincipalIdentifier
    {
        return $this->editorIdentifier;
    }

    public function translation(): Language
    {
        return $this->language;
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
