<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\ListAccountSnapshotChangeRequests;

use Source\Account\Account\Application\UseCase\Query\AccountSnapshotChangeRequestReadModel;

interface ListAccountSnapshotChangeRequestsOutputPort
{
    /** @param AccountSnapshotChangeRequestReadModel[] $requests */
    public function output(array $requests, int $currentPage, int $lastPage, int $total, int $perPage): void;

    /** @return array{requests:list<array{requestIdentifier:string,snapshotIdentifier:string,accountIdentifier:string,changeReason:string,reviewedSnapshotIdentifier:?string,status:string,requestedAt:string,reviewedBy:?string,reviewedAt:?string,rejectionReason:array{code:string,detail:?string}|null}>,pagination:array{currentPage:int,lastPage:int,total:int,perPage:int}} */
    public function toArray(): array;
}
