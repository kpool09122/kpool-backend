<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class WikiListItemReadModel
{
    public function __construct(
        private string $wikiIdentifier,
        private string $slug,
        private string $language,
        private string $resourceType,
        private int $version,
        private ?string $themeColor,
        private string $name,
        private string $normalizedName,
        private ?string $publishedAt,
        private ?string $updatedAt,
    ) {
    }

    public function wikiIdentifier(): string
    {
        return $this->wikiIdentifier;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'wikiIdentifier' => $this->wikiIdentifier,
            'slug' => $this->slug,
            'language' => $this->language,
            'resourceType' => $this->resourceType,
            'version' => $this->version,
            'themeColor' => $this->themeColor,
            'name' => $this->name,
            'normalizedName' => $this->normalizedName,
            'publishedAt' => $this->publishedAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
