<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;

interface ListWikisOutputPort
{
    /**
     * @param list<WikiListItemReadModel> $wikis
     */
    public function output(array $wikis, int $currentPage, int $lastPage, int $total, int $perPage): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
