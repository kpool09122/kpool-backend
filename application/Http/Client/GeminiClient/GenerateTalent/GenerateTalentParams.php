<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\GenerateTalent;

use Source\Wiki\Shared\Application\DTO\SourceReference;

final readonly class GenerateTalentParams
{
    /**
     * @param SourceReference[] $sources
     */
    public function __construct(
        private ?string $alphabetName,
        private ?string $realName,
        private ?string $birthday,
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
            realName: $data['real_name'] ?? null,
            birthday: $data['birthday'] ?? null,
            description: $data['description'] ?? null,
            sources: $sources,
        );
    }

    public static function empty(): self
    {
        return new self(
            alphabetName: null,
            realName: null,
            birthday: null,
            description: null,
            sources: [],
        );
    }

    public function alphabetName(): ?string
    {
        return $this->alphabetName;
    }

    public function realName(): ?string
    {
        return $this->realName;
    }

    public function birthday(): ?string
    {
        return $this->birthday;
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
