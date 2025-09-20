<?php

declare(strict_types=1);

namespace Businesses\Wiki\Group\UseCase\Query\GetGroups;

use Businesses\Wiki\Group\UseCase\Query\GroupReadModel;

interface GetGroupsOutputPort
{
    /**
     * @param GroupReadModel[] $groups
     * @param int $currentPage
     * @param int $lastPage
     * @param int $total
     * @return void
     */
    public function output(
        array $groups,
        int $currentPage,
        int $lastPage,
        int $total,
    ): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
