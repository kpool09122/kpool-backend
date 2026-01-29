<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong;

use Source\Wiki\Shared\Application\DTO\SourceReference;

final readonly class GeneratedSongData
{
    /**
     * @param string|null $alphabetName
     * @param string|null $lyricist
     * @param string|null $composer
     * @param string|null $releaseDate
     * @param string|null $overview
     * @param SourceReference[] $sources
     */
    public function __construct(
        private ?string $alphabetName,
        private ?string $lyricist,
        private ?string $composer,
        private ?string $releaseDate,
        private ?string $overview,
        private array $sources,
    ) {
    }

    public function alphabetName(): ?string
    {
        return $this->alphabetName;
    }

    public function lyricist(): ?string
    {
        return $this->lyricist;
    }

    public function composer(): ?string
    {
        return $this->composer;
    }

    public function releaseDate(): ?string
    {
        return $this->releaseDate;
    }

    public function overview(): ?string
    {
        return $this->overview;
    }

    /**
     * @return SourceReference[]
     */
    public function sources(): array
    {
        return $this->sources;
    }
}
