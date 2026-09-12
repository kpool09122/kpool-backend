<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Query\ListDelegations;

use Source\Account\Delegation\Application\UseCase\Query\DelegationReadModel;

interface ListDelegationsOutputPort
{
    /** @param DelegationReadModel[] $delegations */
    public function output(array $delegations, int $currentPage, int $lastPage, int $total, int $perPage): void;

    /** @return array{delegations: array<int, array<string, mixed>>, current_page: int|null, last_page: int|null, total: int|null, per_page: int|null} */
    public function toArray(): array;
}
