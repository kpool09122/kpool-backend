<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient;

final readonly class GeneratedWikiMetadata
{
    /** @param list<string> $keywords */
    public function __construct(
        private ?string $title = null,
        private ?string $metaDescription = null,
        private array $keywords = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            title: is_string($data['title'] ?? null) ? $data['title'] : null,
            metaDescription: is_string($data['meta_description'] ?? null) ? $data['meta_description'] : null,
            keywords: is_array($data['keywords'] ?? null)
                ? array_values(array_filter($data['keywords'], is_string(...))) : [],
        );
    }

    public function title(): ?string
    {
        return $this->title;
    }

    public function metaDescription(): ?string
    {
        return $this->metaDescription;
    }

    /** @return list<string> */
    public function keywords(): array
    {
        return $this->keywords;
    }
}
