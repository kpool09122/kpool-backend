<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\GenerateAgency;

use Source\Wiki\Shared\Application\DTO\SourceReference;

final readonly class GenerateAgencyParams
{
    /**
     * @param string[] $artists
     * @param string[] $socialLinks
     * @param SourceReference[] $sources
     */
    public function __construct(
        private ?string $alphabetName,
        private ?string $ceoName,
        private ?int $foundedYear,
        private ?string $foundedIn,
        private ?string $status,
        private ?string $officialWebsite,
        private array $socialLinks,
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
            alphabetName: self::nullableString($data['alphabet_name'] ?? null),
            ceoName: self::nullableString($data['ceo'] ?? $data['ceo_name'] ?? null),
            foundedYear: self::nullableInt($data['founded_year'] ?? null),
            foundedIn: self::nullableString($data['founded_in'] ?? null),
            status: self::nullableString($data['status'] ?? null),
            officialWebsite: self::nullableString($data['official_website'] ?? null),
            socialLinks: self::extractSocialLinks($data),
            overview: self::nullableString($data['overview'] ?? null),
            history: self::nullableString($data['history'] ?? null),
            artists: self::stringArray($data['artists'] ?? []),
            sources: $sources,
        );
    }

    public static function empty(): self
    {
        return new self(
            alphabetName: null,
            ceoName: null,
            foundedYear: null,
            foundedIn: null,
            status: null,
            officialWebsite: null,
            socialLinks: [],
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

    public function foundedIn(): ?string
    {
        return $this->foundedIn;
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function officialWebsite(): ?string
    {
        return $this->officialWebsite;
    }

    /**
     * @return string[]
     */
    public function socialLinks(): array
    {
        return $this->socialLinks;
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

    /**
     * @param array<string, mixed> $data
     * @return string[]
     */
    private static function extractSocialLinks(array $data): array
    {
        $links = [];
        if (is_array($data['social_links'] ?? null)) {
            $links = array_values($data['social_links']);
        }

        foreach (['instagram_url', 'tiktok_url', 'youtube_url', 'x_url'] as $key) {
            if (is_string($data[$key] ?? null)) {
                $links[] = $data[$key];
            }
        }

        return array_values(array_filter($links, static fn (mixed $link) => is_string($link) && $link !== ''));
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
