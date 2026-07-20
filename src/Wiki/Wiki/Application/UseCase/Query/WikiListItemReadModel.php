<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class WikiListItemReadModel
{
    public function __construct(
        private string $wikiIdentifier,
        private string $translationSetIdentifier,
        private string $slug,
        private string $language,
        private string $resourceType,
        private int $version,
        private ?string $themeColor,
        private ?string $imageIdentifier,
        private ?string $imageUrl,
        private ?string $imageAltText,
        private ?bool $isHidden,
        private string $name,
        private string $normalizedName,
        private ?string $publishedAt,
        private ?string $updatedAt,
        private ?string $title = null,
        private ?string $metaDescription = null,
        /** @var list<string>|null */
        private ?array $keywords = null,
        private ?string $fontStyle = null,
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
            'translationSetIdentifier' => $this->translationSetIdentifier,
            'slug' => $this->slug,
            'language' => $this->language,
            'resourceType' => $this->resourceType,
            'version' => $this->version,
            'themeColor' => $this->themeColor,
            'fontStyle' => $this->fontStyle,
            'title' => $this->title,
            'metaDescription' => $this->metaDescription,
            'keywords' => $this->keywords,
            'imageIdentifier' => $this->imageIdentifier,
            'imageUrl' => $this->imageUrl,
            'imageAltText' => $this->imageAltText,
            'isHidden' => $this->isHidden,
            'name' => $this->name,
            'normalizedName' => $this->normalizedName,
            'publishedAt' => $this->publishedAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
