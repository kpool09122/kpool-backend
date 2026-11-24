<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest;

use Source\Account\Account\Application\UseCase\Query\AccountSnapshotChangeRequestReadModel;

class GetAccountSnapshotChangeRequestOutput implements GetAccountSnapshotChangeRequestOutputPort
{
    private ?AccountSnapshotChangeRequestReadModel $request = null;

    public function output(AccountSnapshotChangeRequestReadModel $request): void
    {
        $this->request = $request;
    }

    public function request(): ?AccountSnapshotChangeRequestReadModel
    {
        return $this->request;
    }

    /** @return array{}|array{request:array{requestIdentifier:string,snapshotIdentifier:string,accountIdentifier:string,changeReason:string,reviewedSnapshotIdentifier:?string,status:string,requestedAt:string,reviewedBy:?string,reviewedAt:?string,rejectionReason:array{code:string,detail:?string}|null},original:array{snapshotIdentifier:string,accountIdentifier:string,status:string,savedAt:string,isCurrent:bool,account:array<string,string|null>,documents:list<array{documentType:string,uploadedAt:string}>},proposal:array{account:array<string,string|null>,documents:list<array{documentType:string,uploadedAt:string}>}} */
    public function toArray(): array
    {
        return $this->request?->toArray() ?? [];
    }
}
