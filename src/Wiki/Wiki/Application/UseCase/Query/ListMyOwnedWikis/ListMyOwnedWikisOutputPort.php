<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis;

use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;

interface ListMyOwnedWikisOutputPort
{
    /**
     * @param list<WikiListItemReadModel> $primaryOwnedWikis
     * @param list<WikiListItemReadModel> $otherOwnedWikis
     */
    public function output(
        AccountCategory $accountCategory,
        array $primaryOwnedWikis,
        array $otherOwnedWikis,
        int $currentPage,
        int $lastPage,
        int $total,
        int $perPage,
    ): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
