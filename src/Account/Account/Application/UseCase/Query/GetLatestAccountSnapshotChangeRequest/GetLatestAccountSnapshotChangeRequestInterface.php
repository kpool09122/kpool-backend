<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetLatestAccountSnapshotChangeRequest;

use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;

interface GetLatestAccountSnapshotChangeRequestInterface
{
    /** @throws AccountSnapshotNotFoundException
     * @throws AccountSnapshotChangeRequestForbiddenException */
    public function process(GetLatestAccountSnapshotChangeRequestInputPort $input, GetLatestAccountSnapshotChangeRequestOutputPort $output): void;
}
