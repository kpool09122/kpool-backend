<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetLatestAccountSnapshotChangeRequest;

use Source\Account\Account\Application\UseCase\Query\AccountSnapshotChangeRequestReadModel;

interface GetLatestAccountSnapshotChangeRequestOutputPort
{
    public function output(?AccountSnapshotChangeRequestReadModel $request, bool $changeRequiresApproval): void;
}
