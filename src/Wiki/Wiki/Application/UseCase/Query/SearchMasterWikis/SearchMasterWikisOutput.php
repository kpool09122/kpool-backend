<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\WikiMasterSearchItemReadModel;

class SearchMasterWikisOutput implements SearchMasterWikisOutputPort
{
    /** @var list<WikiMasterSearchItemReadModel> */
    private array $wikis = [];

    /**
     * @param list<WikiMasterSearchItemReadModel> $wikis
     */
    public function output(array $wikis): void
    {
        $this->wikis = $wikis;
    }

    /**
     * @return array{wikis: list<array{id: string, name: string, slug: string, resourceType: string}>}
     */
    public function toArray(): array
    {
        return [
            'wikis' => array_map(static fn (WikiMasterSearchItemReadModel $wiki): array => $wiki->toArray(), $this->wikis),
        ];
    }
}
