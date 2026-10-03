<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query;

readonly class DraftImageReadModel
{
    public function __construct(
        private string $imageIdentifier,
        private ?string $publishedImageIdentifier,
        private string $url,
        private string $resourceType,
        private string $translationSetIdentifier,
        private int $displayOrder,
        private string $sourceUrl,
        private string $sourceName,
        private string $altText,
        /** @var array{names: array<string, string>, slug: string} */
        private array $wiki,
        private string $status,
        private ?string $uploadedAt,
    ) {
    }

    /**
     * @return array{imageIdentifier: string, publishedImageIdentifier: string|null, url: string, resourceType: string, translationSetIdentifier: string, displayOrder: int, sourceUrl: string, sourceName: string, altText: string, wiki: array{names: array<string, string>, slug: string}, status: string, uploadedAt: string|null}
     */
    public function toArray(): array
    {
        return [
            'imageIdentifier' => $this->imageIdentifier,
            'publishedImageIdentifier' => $this->publishedImageIdentifier,
            'url' => $this->url,
            'resourceType' => $this->resourceType,
            'translationSetIdentifier' => $this->translationSetIdentifier,
            'displayOrder' => $this->displayOrder,
            'sourceUrl' => $this->sourceUrl,
            'sourceName' => $this->sourceName,
            'altText' => $this->altText,
            'wiki' => $this->wiki,
            'status' => $this->status,
            'uploadedAt' => $this->uploadedAt,
        ];
    }
}
