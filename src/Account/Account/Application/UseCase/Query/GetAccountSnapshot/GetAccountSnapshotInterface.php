<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetAccountSnapshot;

use Source\Account\Account\Application\Exception\AccountSnapshotForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;
use Source\Account\Account\Application\UseCase\Query\AccountSnapshotReadModel;

interface GetAccountSnapshotInterface
{
    /** @throws AccountSnapshotForbiddenException
     * @throws AccountSnapshotNotFoundException
     */
    public function process(GetAccountSnapshotInputPort $input): AccountSnapshotReadModel;
}
