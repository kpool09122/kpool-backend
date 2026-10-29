<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class SitemapWikiReadModel
{
    public function __construct(
        private string $slug,
        private string $language,
        private string $resourceType,
        private ?string $lastModifiedAt,
    ) {
    }

    /** @return array{slug: string, language: string, resourceType: string, lastModifiedAt: string|null} */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'language' => $this->language,
            'resourceType' => $this->resourceType,
            'lastModifiedAt' => $this->lastModifiedAt,
        ];
    }
}
