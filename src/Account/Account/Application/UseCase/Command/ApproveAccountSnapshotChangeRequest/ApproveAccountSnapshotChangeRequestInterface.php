<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\ApproveAccountSnapshotChangeRequest;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestNotFoundException;
use Source\Account\Account\Application\Exception\DocumentStorageFailedException;
use Source\Account\Account\Domain\Exception\InvalidAccountSnapshotChangeRequestApprovalException;

interface ApproveAccountSnapshotChangeRequestInterface
{
    /**
     * @throws AccountNotFoundException
     * @throws DocumentStorageFailedException
     * @throws AccountSnapshotChangeRequestNotFoundException
     * @throws AccountSnapshotChangeRequestForbiddenException
     * @throws InvalidAccountSnapshotChangeRequestApprovalException
     */
    public function process(ApproveAccountSnapshotChangeRequestInputPort $input, ApproveAccountSnapshotChangeRequestOutputPort $output): void;
}
