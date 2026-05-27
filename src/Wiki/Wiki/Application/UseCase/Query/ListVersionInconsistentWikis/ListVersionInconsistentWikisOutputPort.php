<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;

interface ListVersionInconsistentWikisOutputPort
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
