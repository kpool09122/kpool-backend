<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class RelatedProfileReadModel
{
    public function __construct(
        private string $wikiIdentifier,
        private string $slug,
        private string $language,
        private string $resourceType,
        private string $name,
        private string $normalizedName,
        private ?string $imageIdentifier,
        private ?string $imageUrl,
        private ?string $imageAltText,
    ) {
    }

    public function wikiIdentifier(): string
    {
        return $this->wikiIdentifier;
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
            'name' => $this->name,
            'normalizedName' => $this->normalizedName,
            'imageIdentifier' => $this->imageIdentifier,
            'imageUrl' => $this->imageUrl,
            'imageAltText' => $this->imageAltText,
        ];
    }
}
