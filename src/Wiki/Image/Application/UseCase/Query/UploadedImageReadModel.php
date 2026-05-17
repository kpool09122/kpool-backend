<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query;

readonly class UploadedImageReadModel
{
    public function __construct(
        private string $imageIdentifier,
        private string $url,
        private string $resourceType,
        private string $translationSetIdentifier,
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
            'translationSetIdentifier' => $this->translationSetIdentifier,
            'displayOrder' => $this->displayOrder,
            'sourceUrl' => $this->sourceUrl,
            'sourceName' => $this->sourceName,
            'altText' => $this->altText,
            'isHidden' => $this->isHidden,
            'uploadedAt' => $this->uploadedAt,
        ];
    }
}
