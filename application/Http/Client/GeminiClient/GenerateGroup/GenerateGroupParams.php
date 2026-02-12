<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\GenerateGroup;

use Source\Wiki\Shared\Application\DTO\SourceReference;

final readonly class GenerateGroupParams
{
    /**
     * @param string[] $representativeSongs
     * @param string[] $awards
     * @param string[] $members
     * @param SourceReference[] $sources
     */
    public function __construct(
        private ?string $alphabetName,
        private ?string $overview,
        private ?string $history,
        private array $representativeSongs,
        private array $awards,
        private array $members,
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
            overview: $data['overview'] ?? null,
            history: $data['history'] ?? null,
            representativeSongs: $data['representative_songs'] ?? [],
            awards: $data['awards'] ?? [],
            members: $data['members'] ?? [],
            sources: $sources,
        );
    }

    public static function empty(): self
    {
        return new self(
            alphabetName: null,
            overview: null,
            history: null,
            representativeSongs: [],
            awards: [],
            members: [],
            sources: [],
        );
    }

    public function alphabetName(): ?string
    {
        return $this->alphabetName;
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
    public function representativeSongs(): array
    {
        return $this->representativeSongs;
    }

    /**
     * @return string[]
     */
    public function awards(): array
    {
        return $this->awards;
    }

    /**
     * @return string[]
     */
    public function members(): array
    {
        return $this->members;
    }

    /**
     * @return SourceReference[]
     */
    public function sources(): array
    {
        return $this->sources;
    }
}
