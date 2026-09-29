<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;

class ListRelatedWikisOutput implements ListRelatedWikisOutputPort
{
    /** @var list<WikiListItemReadModel> */
    private array $wikis = [];

    /** @param list<WikiListItemReadModel> $wikis */
    public function output(array $wikis): void
    {
        $this->wikis = $wikis;
    }

    /** @return array{wikis: list<array{wikiIdentifier: string, translationSetIdentifier: string, slug: string, language: string, resourceType: string, version: int, isOfficial: bool, themeColor: string|null, fontStyle: string|null, title: string|null, metaDescription: string|null, keywords: list<string>|null, imageIdentifier: string|null, imageUrl: string|null, imageAltText: string|null, isHidden: bool|null, name: string, normalizedName: string, publishedAt: string|null, updatedAt: string|null}>} */
    public function toArray(): array
    {
        return [
            'wikis' => array_map(static fn (WikiListItemReadModel $wiki): array => $wiki->toArray(), $this->wikis),
        ];
    }
}
