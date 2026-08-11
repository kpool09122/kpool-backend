<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests;

use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestListItemReadModel;

interface ListAccountCategoryChangeRequestsOutputPort
{
    /** @param AccountCategoryChangeRequestListItemReadModel[] $requests */
    public function output(array $requests, int $currentPage, int $lastPage, int $total, int $perPage): void;

    /** @return array{requests: array<int, array<string, mixed>>, current_page: int|null, last_page: int|null, total: int|null, per_page: int|null} */
    public function toArray(): array;
}
