<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest;

use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestNotFoundException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;

interface GetAccountSnapshotChangeRequestInterface
{
    /**
     * @throws AccountSnapshotChangeRequestNotFoundException
     * @throws AccountSnapshotChangeRequestForbiddenException
     * @throws AccountSnapshotNotFoundException
     */
    public function process(GetAccountSnapshotChangeRequestInputPort $input, GetAccountSnapshotChangeRequestOutputPort $output): void;
}
