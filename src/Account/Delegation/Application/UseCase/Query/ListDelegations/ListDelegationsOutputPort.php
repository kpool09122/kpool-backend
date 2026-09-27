<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Query\ListDelegations;

use Source\Account\Delegation\Application\UseCase\Query\DelegationReadModel;

interface ListDelegationsOutputPort
{
    /** @param DelegationReadModel[] $delegations */
    public function output(array $delegations, int $currentPage, int $lastPage, int $total, int $perPage): void;

    /** @return array{delegations: array<array{ delegationIdentifier: string, affiliationIdentifier: string, delegateAccountIdentifier: string, delegatorAccountIdentifier: string, requestedByAccountIdentifier: string, delegateAccount: array{accountIdentifier: string, name: string, email: string}, delegatorAccount: array{accountIdentifier: string, name: string, email: string}, requestedByAccount: array{accountIdentifier: string, name: string, email: string}, status: string, direction: string, requestedAt: string, approvedAt: string|null, rejectedAt: string|null }>, current_page: int|null, last_page: int|null, total: int|null, per_page: int|null} */
    public function toArray(): array;
}
