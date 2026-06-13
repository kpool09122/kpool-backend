<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\GenerateSong;

use Source\Wiki\Shared\Application\DTO\SourceReference;

final readonly class GenerateSongParams
{
    /**
     * @param string[] $chartPerformance
     * @param string[] $genres
     * @param SourceReference[] $sources
     */
    public function __construct(
        private ?string $alphabetName,
        private ?string $songType,
        private array $genres,
        private ?string $lyricist,
        private ?string $composer,
        private ?string $arranger,
        private ?string $releaseDate,
        private ?string $albumName,
        private ?string $overview,
        private array $chartPerformance,
        private array $sources,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param SourceReference[] $sources
     */
    public static function fromArray(array $data, array $sources): self
    {
        $alphabetName = self::nullableString($data['alphabet_name'] ?? null);
        $lyricist = self::nullableString($data['lyricist'] ?? null);
        $composer = self::nullableString($data['composer'] ?? null);
        $arranger = self::nullableString($data['arranger'] ?? null);
        $releaseDate = self::nullableString($data['release_date'] ?? null);
        $albumName = self::nullableString($data['album_name'] ?? null);
        $overview = self::nullableString($data['overview'] ?? null);

        return new self(
            alphabetName: $alphabetName,
            songType: self::nullableString($data['song_type'] ?? null),
            genres: self::stringArray($data['genres'] ?? []),
            lyricist: $lyricist,
            composer: $composer,
            arranger: $arranger,
            releaseDate: $releaseDate,
            albumName: $albumName,
            overview: $overview,
            chartPerformance: self::stringArray($data['chart_performance'] ?? []),
            sources: $sources,
        );
    }

    public static function empty(): self
    {
        return new self(
            alphabetName: null,
            songType: null,
            genres: [],
            lyricist: null,
            composer: null,
            arranger: null,
            releaseDate: null,
            albumName: null,
            overview: null,
            chartPerformance: [],
            sources: [],
        );
    }

    public function alphabetName(): ?string
    {
        return $this->alphabetName;
    }

    public function songType(): ?string
    {
        return $this->songType;
    }

    /**
     * @return string[]
     */
    public function genres(): array
    {
        return $this->genres;
    }

    public function lyricist(): ?string
    {
        return $this->lyricist;
    }

    public function composer(): ?string
    {
        return $this->composer;
    }

    public function arranger(): ?string
    {
        return $this->arranger;
    }

    public function releaseDate(): ?string
    {
        return $this->releaseDate;
    }

    public function albumName(): ?string
    {
        return $this->albumName;
    }

    public function overview(): ?string
    {
        return $this->overview;
    }

    /**
     * @return string[]
     */
    public function chartPerformance(): array
    {
        return $this->chartPerformance;
    }

    /**
     * @return SourceReference[]
     */
    public function sources(): array
    {
        return $this->sources;
    }

    /**
     * @return string[]
     */
    private static function stringArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn (mixed $item) => is_string($item)));
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
