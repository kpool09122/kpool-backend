<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\GenerateAgency;

use Source\Wiki\Shared\Application\DTO\SourceReference;

final readonly class GenerateAgencyParams
{
    /**
     * @param SourceReference[] $sources
     */
    public function __construct(
        private ?string $alphabetName,
        private ?string $ceoName,
        private ?int $foundedYear,
        private ?string $description,
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
            foundedYear: isset($data['founded_year']) ? (int)$data['founded_year'] : null,
            description: $data['description'] ?? null,
            sources: $sources,
        );
    }

    public static function empty(): self
    {
        return new self(
            alphabetName: null,
            ceoName: null,
            foundedYear: null,
            description: null,
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

    public function description(): ?string
    {
        return $this->description;
    }

    /**
     * @return SourceReference[]
     */
    public function sources(): array
    {
        return $this->sources;
    }
}
