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
        private ?string $imageIdentifier,
        private ?string $imageUrl,
        private ?string $imageAltText,
        private string $status,
        private ?string $rejectionReason,
        private string $name,
        private string $normalizedName,
        private ?string $editedAt,
        private ?string $approvedAt,
        private ?string $translatedAt,
        private ?string $mergedAt,
        private ?string $title = null,
        private ?string $metaDescription = null,
        /** @var list<string>|null */
        private ?array $keywords = null,
        private ?string $fontStyle = null,
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
            'fontStyle' => $this->fontStyle,
            'title' => $this->title,
            'metaDescription' => $this->metaDescription,
            'keywords' => $this->keywords,
            'imageIdentifier' => $this->imageIdentifier,
            'imageUrl' => $this->imageUrl,
            'imageAltText' => $this->imageAltText,
            'status' => $this->status,
            'rejectionReason' => $this->rejectionReason,
            'name' => $this->name,
            'normalizedName' => $this->normalizedName,
            'editedAt' => $this->editedAt,
            'approvedAt' => $this->approvedAt,
            'translatedAt' => $this->translatedAt,
            'mergedAt' => $this->mergedAt,
        ];
    }
}
