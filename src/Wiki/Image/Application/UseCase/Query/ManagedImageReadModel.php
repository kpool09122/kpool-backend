<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query;

use stdClass;

readonly class ManagedImageReadModel
{
    /** @param array{names: array<string, string>, slug: string} $wiki */
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
        private array $wiki,
        private string $status,
        private ?string $author = null,
        private ?string $license = null,
    ) {
    }

    /**
     * @return array{imageIdentifier: string, url: string, resourceType: string, translationSetIdentifier: string, displayOrder: int, sourceUrl: string, sourceName: string, author: string|null, license: string|null, altText: string, isHidden: bool, uploadedAt: string|null, wiki: array{names: array<string, string>|stdClass, slug: string}, status: string}
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
            'author' => $this->author,
            'license' => $this->license,
            'altText' => $this->altText,
            'isHidden' => $this->isHidden,
            'uploadedAt' => $this->uploadedAt,
            'wiki' => [
                'names' => $this->wiki['names'] === [] ? new stdClass() : $this->wiki['names'],
                'slug' => $this->wiki['slug'],
            ],
            'status' => $this->status,
        ];
    }
}
