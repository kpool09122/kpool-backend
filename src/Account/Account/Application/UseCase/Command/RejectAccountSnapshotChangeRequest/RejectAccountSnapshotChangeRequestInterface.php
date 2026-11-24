<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RejectAccountSnapshotChangeRequest;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestNotFoundException;
use Source\Account\Account\Application\Exception\DocumentStorageFailedException;
use Source\Account\Account\Domain\Exception\InvalidAccountSnapshotChangeRequestRejectionException;

interface RejectAccountSnapshotChangeRequestInterface
{
    /**
     * @throws AccountNotFoundException
     * @throws DocumentStorageFailedException
     * @throws AccountSnapshotChangeRequestNotFoundException
     * @throws AccountSnapshotChangeRequestForbiddenException
     * @throws InvalidAccountSnapshotChangeRequestRejectionException
     */
    public function process(RejectAccountSnapshotChangeRequestInputPort $input, RejectAccountSnapshotChangeRequestOutputPort $output): void;
}
