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
     * @param string[] $officialColors
     * @param SourceReference[] $sources
     */
    public function __construct(
        private ?string $alphabetName,
        private ?string $groupType,
        private ?string $status,
        private ?string $generation,
        private ?string $debutDate,
        private ?string $disbandDate,
        private ?string $fandomName,
        private array $officialColors,
        private ?string $emoji,
        private ?string $representativeSymbol,
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
            alphabetName: self::nullableString($data['alphabet_name'] ?? null),
            groupType: self::nullableString($data['group_type'] ?? null),
            status: self::nullableString($data['status'] ?? null),
            generation: self::nullableString($data['generation'] ?? null),
            debutDate: self::nullableString($data['debut_date'] ?? null),
            disbandDate: self::nullableString($data['disband_date'] ?? null),
            fandomName: self::nullableString($data['fandom_name'] ?? null),
            officialColors: self::officialColorsArray($data['official_colors'] ?? []),
            emoji: self::nullableString($data['emoji'] ?? null),
            representativeSymbol: self::nullableString($data['representative_symbol'] ?? null),
            overview: self::nullableString($data['overview'] ?? null),
            history: self::nullableString($data['history'] ?? null),
            representativeSongs: self::stringArray($data['representative_songs'] ?? []),
            awards: self::stringArray($data['awards'] ?? []),
            members: self::stringArray($data['members'] ?? []),
            sources: $sources,
        );
    }

    public static function empty(): self
    {
        return new self(
            alphabetName: null,
            groupType: null,
            status: null,
            generation: null,
            debutDate: null,
            disbandDate: null,
            fandomName: null,
            officialColors: [],
            emoji: null,
            representativeSymbol: null,
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

    public function groupType(): ?string
    {
        return $this->groupType;
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function generation(): ?string
    {
        return $this->generation;
    }

    public function debutDate(): ?string
    {
        return $this->debutDate;
    }

    public function disbandDate(): ?string
    {
        return $this->disbandDate;
    }

    public function fandomName(): ?string
    {
        return $this->fandomName;
    }

    /**
     * @return list<array{color_code: string, label: string}>
     */
    public function officialColors(): array
    {
        return $this->officialColors;
    }

    public function emoji(): ?string
    {
        return $this->emoji;
    }

    public function representativeSymbol(): ?string
    {
        return $this->representativeSymbol;
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

    /**
     * @return list<array{color_code: string, label: string}>
     */
    private static function officialColorsArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $item) => is_array($item)
                && is_string($item['color_code'] ?? null)
                && is_string($item['label'] ?? null),
        ));
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
