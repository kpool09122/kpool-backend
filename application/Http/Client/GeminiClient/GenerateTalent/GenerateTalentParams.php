<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\GenerateTalent;

use Source\Wiki\Shared\Application\DTO\SourceReference;

final readonly class GenerateTalentParams
{
    /**
     * @param string[] $appearances
     * @param string[] $awards
     * @param SourceReference[] $sources
     */
    public function __construct(
        private ?string $alphabetName,
        private ?string $realName,
        private ?string $birthday,
        private ?string $overview,
        private ?string $history,
        private array $appearances,
        private array $awards,
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
            overview: $data['overview'] ?? null,
            history: $data['history'] ?? null,
            appearances: $data['appearances'] ?? [],
            awards: $data['awards'] ?? [],
            sources: $sources,
        );
    }

    public static function empty(): self
    {
        return new self(
            alphabetName: null,
            realName: null,
            birthday: null,
            overview: null,
            history: null,
            appearances: [],
            awards: [],
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
    public function appearances(): array
    {
        return $this->appearances;
    }

    /**
     * @return string[]
     */
    public function awards(): array
    {
        return $this->awards;
    }

    /**
     * @return SourceReference[]
     */
    public function sources(): array
    {
        return $this->sources;
    }
}
