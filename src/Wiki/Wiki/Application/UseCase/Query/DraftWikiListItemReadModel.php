<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class DraftWikiListItemReadModel
{
    public function __construct(
        private string $wikiIdentifier,
        private ?string $publishedWikiIdentifier,
        private string $translationSetIdentifier,
        private string $slug,
        private string $language,
        private string $resourceType,
        private ?string $themeColor,
        private string $status,
        private string $name,
        private string $normalizedName,
        private ?string $editedAt,
        private ?string $updatedAt,
        private ?string $approvedAt,
        private ?string $translatedAt,
        private ?string $mergedAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'wikiIdentifier' => $this->wikiIdentifier,
            'publishedWikiIdentifier' => $this->publishedWikiIdentifier,
            'translationSetIdentifier' => $this->translationSetIdentifier,
            'slug' => $this->slug,
            'language' => $this->language,
            'resourceType' => $this->resourceType,
            'themeColor' => $this->themeColor,
            'status' => $this->status,
            'name' => $this->name,
            'normalizedName' => $this->normalizedName,
            'editedAt' => $this->editedAt,
            'updatedAt' => $this->updatedAt,
            'approvedAt' => $this->approvedAt,
            'translatedAt' => $this->translatedAt,
            'mergedAt' => $this->mergedAt,
        ];
    }
}
