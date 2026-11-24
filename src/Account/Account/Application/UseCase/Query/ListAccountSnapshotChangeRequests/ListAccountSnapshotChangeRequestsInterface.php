<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\ListAccountSnapshotChangeRequests;

use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;

interface ListAccountSnapshotChangeRequestsInterface
{
    /**
     * @throws AccountSnapshotChangeRequestForbiddenException
     * @throws AccountSnapshotNotFoundException
     */
    public function process(ListAccountSnapshotChangeRequestsInputPort $input, ListAccountSnapshotChangeRequestsOutputPort $output): void;
}
