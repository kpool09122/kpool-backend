<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetLatestAccountSnapshotChangeRequest;

use Source\Account\Account\Application\UseCase\Query\AccountSnapshotChangeRequestReadModel;

class GetLatestAccountSnapshotChangeRequestOutput implements GetLatestAccountSnapshotChangeRequestOutputPort
{
    private ?AccountSnapshotChangeRequestReadModel $request = null;
    private bool $changeRequiresApproval = false;

    public function output(?AccountSnapshotChangeRequestReadModel $request, bool $changeRequiresApproval): void
    {
        $this->request = $request;
        $this->changeRequiresApproval = $changeRequiresApproval;
    }

    /** @return array{request:array{requestIdentifier:string,snapshotIdentifier:string,accountIdentifier:string,changeReason:string,reviewedSnapshotIdentifier:?string,status:string,requestedAt:string,reviewedBy:?string,reviewedAt:?string,rejectionReason:array{code:string,detail:?string}|null}|null,changeRequiresApproval:bool} */
    public function toArray(): array
    {
        return ['request' => $this->request?->summary(), 'changeRequiresApproval' => $this->changeRequiresApproval];
    }
}
