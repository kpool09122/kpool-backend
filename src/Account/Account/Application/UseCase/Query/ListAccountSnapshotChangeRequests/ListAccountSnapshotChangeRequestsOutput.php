<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\ListAccountSnapshotChangeRequests;

use Source\Account\Account\Application\UseCase\Query\AccountSnapshotChangeRequestReadModel;

class ListAccountSnapshotChangeRequestsOutput implements ListAccountSnapshotChangeRequestsOutputPort
{
    /** @var AccountSnapshotChangeRequestReadModel[] */
    private array $requests = [];
    private int $currentPage = 1;
    private int $lastPage = 1;
    private int $total = 0;
    private int $perPage = 20;

    public function output(array $requests, int $currentPage, int $lastPage, int $total, int $perPage): void
    {
        $this->requests = $requests;
        $this->currentPage = $currentPage;
        $this->lastPage = $lastPage;
        $this->total = $total;
        $this->perPage = $perPage;
    }

    /** @return array{requests:list<array{requestIdentifier:string,snapshotIdentifier:string,accountIdentifier:string,changeReason:string,reviewedSnapshotIdentifier:?string,status:string,requestedAt:string,reviewedBy:?string,reviewedAt:?string,rejectionReason:array{code:string,detail:?string}|null}>,pagination:array{currentPage:int,lastPage:int,total:int,perPage:int}} */
    public function toArray(): array
    {
        return ['requests' => array_values(array_map(static fn (AccountSnapshotChangeRequestReadModel $request): array => $request->summary(), $this->requests)), 'pagination' => ['currentPage' => $this->currentPage, 'lastPage' => $this->lastPage, 'total' => $this->total, 'perPage' => $this->perPage]];
    }
}
