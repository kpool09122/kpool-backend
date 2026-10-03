<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;

class ListVersionInconsistentWikisOutput implements ListVersionInconsistentWikisOutputPort
{
    /** @var list<WikiListItemReadModel> */
    private array $wikis = [];

    private ?int $currentPage = null;

    private ?int $lastPage = null;

    private ?int $total = null;

    private ?int $perPage = null;

    /**
     * @param list<WikiListItemReadModel> $wikis
     */
    public function output(array $wikis, int $currentPage, int $lastPage, int $total, int $perPage): void
    {
        $this->wikis = $wikis;
        $this->currentPage = $currentPage;
        $this->lastPage = $lastPage;
        $this->total = $total;
        $this->perPage = $perPage;
    }

    /**
     * @return array{wikis: list<array{wikiIdentifier: string, translationSetIdentifier: string, slug: string, language: string, resourceType: string, version: int, isOfficial: bool, themeColor: string|null, fontStyle: string|null, title: string|null, metaDescription: string|null, keywords: list<string>|null, imageIdentifier: string|null, imageUrl: string|null, imageAltText: string|null, isHidden: bool|null, name: string, normalizedName: string, publishedAt: string|null, updatedAt: string|null}>, current_page: int|null, last_page: int|null, total: int|null, per_page: int|null}
     */
    public function toArray(): array
    {
        return [
            'wikis' => array_map(static fn (WikiListItemReadModel $wiki): array => $wiki->toArray(), $this->wikis),
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'total' => $this->total,
            'per_page' => $this->perPage,
        ];
    }
}
