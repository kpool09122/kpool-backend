<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\SitemapWikiReadModel;

class ListSitemapWikisOutput implements ListSitemapWikisOutputPort
{
    /** @var list<SitemapWikiReadModel> */
    private array $wikis = [];

    private ?string $nextCursor = null;

    /** @param list<SitemapWikiReadModel> $wikis */
    public function output(array $wikis, ?string $nextCursor): void
    {
        $this->wikis = $wikis;
        $this->nextCursor = $nextCursor;
    }

    /** @return array{wikis: list<array{slug: string, language: string, resourceType: string, lastModifiedAt: string|null}>, nextCursor: string|null} */
    public function toArray(): array
    {
        return [
            'wikis' => array_map(static fn (SitemapWikiReadModel $wiki): array => $wiki->toArray(), $this->wikis),
            'nextCursor' => $this->nextCursor,
        ];
    }
}
