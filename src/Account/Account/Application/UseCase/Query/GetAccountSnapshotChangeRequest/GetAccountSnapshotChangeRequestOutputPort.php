<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest;

use Source\Account\Account\Application\UseCase\Query\AccountSnapshotChangeRequestReadModel;

interface GetAccountSnapshotChangeRequestOutputPort
{
    public function output(AccountSnapshotChangeRequestReadModel $detail): void;
}
