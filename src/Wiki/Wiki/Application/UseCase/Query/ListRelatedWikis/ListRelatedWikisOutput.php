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

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'wikis' => array_map(static fn (WikiListItemReadModel $wiki): array => $wiki->toArray(), $this->wikis),
        ];
    }
}
