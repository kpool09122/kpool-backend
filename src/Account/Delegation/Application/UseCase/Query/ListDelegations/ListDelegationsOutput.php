<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Query\ListDelegations;

use Source\Account\Delegation\Application\UseCase\Query\DelegationReadModel;

class ListDelegationsOutput implements ListDelegationsOutputPort
{
    /** @var DelegationReadModel[] */
    private array $delegations = [];
    private ?int $currentPage = null;
    private ?int $lastPage = null;
    private ?int $total = null;
    private ?int $perPage = null;

    /** @param DelegationReadModel[] $delegations */
    public function output(array $delegations, int $currentPage, int $lastPage, int $total, int $perPage): void
    {
        $this->delegations = $delegations;
        $this->currentPage = $currentPage;
        $this->lastPage = $lastPage;
        $this->total = $total;
        $this->perPage = $perPage;
    }

    /** @return array{delegations: array<array{ delegationIdentifier: string, affiliationIdentifier: string, delegateAccountIdentifier: string, delegatorAccountIdentifier: string, requestedByAccountIdentifier: string, delegateAccount: array{accountIdentifier: string, name: string, email: string}, delegatorAccount: array{accountIdentifier: string, name: string, email: string}, requestedByAccount: array{accountIdentifier: string, name: string, email: string}, status: string, direction: string, requestedAt: string, approvedAt: string|null, rejectedAt: string|null }>, current_page: int|null, last_page: int|null, total: int|null, per_page: int|null} */
    public function toArray(): array
    {
        return [
            'delegations' => array_map(static fn (DelegationReadModel $delegation): array => $delegation->toArray(), $this->delegations),
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'total' => $this->total,
            'per_page' => $this->perPage,
        ];
    }
}
