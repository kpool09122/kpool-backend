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
        private ?string $position,
        private ?string $mbti,
        private ?string $zodiacSign,
        private ?string $englishLevel,
        private ?int $height,
        private ?string $bloodType,
        private ?string $fandomName,
        private ?string $emoji,
        private ?string $representativeSymbol,
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
            alphabetName: self::nullableString($data['alphabet_name'] ?? null),
            realName: self::nullableString($data['real_name'] ?? null),
            birthday: self::nullableString($data['birthday'] ?? null),
            position: self::nullableString($data['position'] ?? null),
            mbti: self::nullableString($data['mbti'] ?? null),
            zodiacSign: self::nullableString($data['zodiac_sign'] ?? null),
            englishLevel: self::nullableString($data['english_level'] ?? null),
            height: self::nullableInt($data['height'] ?? null),
            bloodType: self::nullableString($data['blood_type'] ?? null),
            fandomName: self::nullableString($data['fandom_name'] ?? null),
            emoji: self::nullableString($data['emoji'] ?? null),
            representativeSymbol: self::nullableString($data['representative_symbol'] ?? null),
            overview: self::nullableString($data['overview'] ?? null),
            history: self::nullableString($data['history'] ?? null),
            appearances: self::stringArray($data['appearances'] ?? []),
            awards: self::stringArray($data['awards'] ?? []),
            sources: $sources,
        );
    }

    public static function empty(): self
    {
        return new self(
            alphabetName: null,
            realName: null,
            birthday: null,
            position: null,
            mbti: null,
            zodiacSign: null,
            englishLevel: null,
            height: null,
            bloodType: null,
            fandomName: null,
            emoji: null,
            representativeSymbol: null,
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

    public function position(): ?string
    {
        return $this->position;
    }

    public function mbti(): ?string
    {
        return $this->mbti;
    }

    public function zodiacSign(): ?string
    {
        return $this->zodiacSign;
    }

    public function englishLevel(): ?string
    {
        return $this->englishLevel;
    }

    public function height(): ?int
    {
        return $this->height;
    }

    public function bloodType(): ?string
    {
        return $this->bloodType;
    }

    public function fandomName(): ?string
    {
        return $this->fandomName;
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

    private static function nullableInt(mixed $value): ?int
    {
        return is_int($value) || is_float($value) || is_string($value) && is_numeric($value)
            ? (int) $value
            : null;
    }
}
