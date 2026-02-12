<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\GenerateAgency;

use Source\Wiki\Shared\Application\DTO\SourceReference;

final readonly class GenerateAgencyParams
{
    /**
     * @param string[] $artists
     * @param SourceReference[] $sources
     */
    public function __construct(
        private ?string $alphabetName,
        private ?string $ceoName,
        private ?int $foundedYear,
        private ?string $overview,
        private ?string $history,
        private array $artists,
        private array $sources,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param SourceReference[] $sources
     */
    public static function fromArray(array $data, array $sources): self
    {
        return new self(
            alphabetName: $data['alphabet_name'] ?? null,
            ceoName: $data['ceo_name'] ?? null,
            foundedYear: isset($data['founded_year']) ? (int) $data['founded_year'] : null,
            overview: $data['overview'] ?? null,
            history: $data['history'] ?? null,
            artists: $data['artists'] ?? [],
            sources: $sources,
        );
    }

    public static function empty(): self
    {
        return new self(
            alphabetName: null,
            ceoName: null,
            foundedYear: null,
            overview: null,
            history: null,
            artists: [],
            sources: [],
        );
    }

    public function alphabetName(): ?string
    {
        return $this->alphabetName;
    }

    public function ceoName(): ?string
    {
        return $this->ceoName;
    }

    public function foundedYear(): ?int
    {
        return $this->foundedYear;
    }

    public function overview(): ?string
    {
        return $this->overview;
    }

    public function history(): ?string
    {
        return $this->history;
    }

    /**
     * @return string[]
     */
    public function artists(): array
    {
        return $this->artists;
    }

    /**
     * @return SourceReference[]
     */
    public function sources(): array
    {
        return $this->sources;
    }
}
