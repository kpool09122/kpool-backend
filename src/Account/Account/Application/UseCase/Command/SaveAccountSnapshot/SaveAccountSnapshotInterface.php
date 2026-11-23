<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountSnapshotForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;
use Source\Account\Account\Application\Exception\DocumentStorageFailedException;
use Source\Account\Account\Application\Exception\IncompleteAccountContactForCategoryChangeException;
use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Domain\Exception\AccountSetupUnavailableException;

interface SaveAccountSnapshotInterface
{
    /** @throws AccountSnapshotForbiddenException
     * @throws AccountSnapshotNotFoundException
     * @throws AccountNotFoundException
     * @throws IncompleteAccountContactForCategoryChangeException
     * @throws AccountSetupUnavailableException
     * @throws InvalidDocumentsForVerificationException
     * @throws DocumentStorageFailedException
     */
    public function process(SaveAccountSnapshotInputPort $input, SaveAccountSnapshotOutputPort $output): void;
}
