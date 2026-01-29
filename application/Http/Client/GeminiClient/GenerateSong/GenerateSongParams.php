<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\GenerateSong;

use Source\Wiki\Shared\Application\DTO\SourceReference;

final readonly class GenerateSongParams
{
    /**
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

    /**
     * @param array<string, mixed> $data
     * @param SourceReference[] $sources
     */
    public static function fromArray(array $data, array $sources): self
    {
        /** @var string|null $alphabetName */
        $alphabetName = $data['alphabet_name'] ?? null;
        /** @var string|null $lyricist */
        $lyricist = $data['lyricist'] ?? null;
        /** @var string|null $composer */
        $composer = $data['composer'] ?? null;
        /** @var string|null $releaseDate */
        $releaseDate = $data['release_date'] ?? null;
        /** @var string|null $overview */
        $overview = $data['overview'] ?? null;

        return new self(
            alphabetName: $alphabetName,
            lyricist: $lyricist,
            composer: $composer,
            releaseDate: $releaseDate,
            overview: $overview,
            sources: $sources,
        );
    }

    public static function empty(): self
    {
        return new self(
            alphabetName: null,
            lyricist: null,
            composer: null,
            releaseDate: null,
            overview: null,
            sources: [],
        );
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
