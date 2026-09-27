<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests;

use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestListItemReadModel;

class ListAccountCategoryChangeRequestsOutput implements ListAccountCategoryChangeRequestsOutputPort
{
    /** @var AccountCategoryChangeRequestListItemReadModel[] */
    private array $requests = [];

    private ?int $currentPage = null;

    private ?int $lastPage = null;

    private ?int $total = null;

    private ?int $perPage = null;

    /** @param AccountCategoryChangeRequestListItemReadModel[] $requests */
    public function output(array $requests, int $currentPage, int $lastPage, int $total, int $perPage): void
    {
        $this->requests = $requests;
        $this->currentPage = $currentPage;
        $this->lastPage = $lastPage;
        $this->total = $total;
        $this->perPage = $perPage;
    }

    /** @return array{requests: array<array{requestIdentifier: string, accountIdentifier: string, currentAccountCategory: string, requestedAccountCategory: string, status: string, requestedAt: string, reviewedBy: ?string, reviewedAt: ?string, rejectionReason: array{code: string, detail: ?string}|null, account: array{accountIdentifier: string, email: string, type: string, name: string, status: string, accountCategory: string, phone: string|null, address: array{countryCode: string|null, administrativeAreaCode: string|null, postalCode: string|null, locality: string|null, addressLine1: string|null, addressLine2: string|null}|null}}>, current_page: int|null, last_page: int|null, total: int|null, per_page: int|null} */
    public function toArray(): array
    {
        return [
            'requests' => array_map(
                static fn (AccountCategoryChangeRequestListItemReadModel $request): array => $request->toArray(),
                $this->requests,
            ),
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'total' => $this->total,
            'per_page' => $this->perPage,
        ];
    }
}
