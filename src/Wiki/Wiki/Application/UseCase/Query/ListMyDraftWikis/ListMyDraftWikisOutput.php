<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListMyDraftWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiListItemReadModel;

class ListMyDraftWikisOutput implements ListMyDraftWikisOutputPort
{
    /** @var list<DraftWikiListItemReadModel> */
    private array $wikis = [];

    private ?string $nextCursor = null;

    private ?int $total = null;

    private ?int $perPage = null;

    /**
     * @param list<DraftWikiListItemReadModel> $wikis
     */
    public function output(array $wikis, ?string $nextCursor, int $total, int $perPage): void
    {
        $this->wikis = $wikis;
        $this->nextCursor = $nextCursor;
        $this->total = $total;
        $this->perPage = $perPage;
    }

    /**
     * @return array{wikis: list<array{wikiIdentifier: string, publishedWikiIdentifier: string|null, translationSetIdentifier: string, slug: string, language: string, resourceType: string, themeColor: string|null, fontStyle: string|null, title: string|null, metaDescription: string|null, keywords: list<string>|null, imageIdentifier: string|null, imageUrl: string|null, imageAltText: string|null, isHidden: bool|null, status: string, rejectionReason: string|null, name: string, normalizedName: string, editedAt: string|null, approvedAt: string|null, translatedAt: string|null, mergedAt: string|null}>, next_cursor: string|null, total: int|null, per_page: int|null}
     */
    public function toArray(): array
    {
        return [
            'wikis' => array_map(static fn (DraftWikiListItemReadModel $wiki): array => $wiki->toArray(), $this->wikis),
            'next_cursor' => $this->nextCursor,
            'total' => $this->total,
            'per_page' => $this->perPage,
        ];
    }
}
