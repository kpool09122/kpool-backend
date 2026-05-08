<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query;

readonly class UploadedImageReadModel
{
    public function __construct(
        private string $imageIdentifier,
        private string $url,
        private string $resourceType,
        private string $wikiIdentifier,
        private string $imageUsage,
        private int $displayOrder,
        private string $sourceUrl,
        private string $sourceName,
        private string $altText,
        private bool $isHidden,
        private ?string $uploadedAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'imageIdentifier' => $this->imageIdentifier,
            'url' => $this->url,
            'resourceType' => $this->resourceType,
            'wikiIdentifier' => $this->wikiIdentifier,
            'imageUsage' => $this->imageUsage,
            'displayOrder' => $this->displayOrder,
            'sourceUrl' => $this->sourceUrl,
            'sourceName' => $this->sourceName,
            'altText' => $this->altText,
            'isHidden' => $this->isHidden,
            'uploadedAt' => $this->uploadedAt,
        ];
    }
}
