<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;
use Source\Account\Account\Application\Exception\IncompleteAccountContactForCategoryChangeException;
use Source\Account\Account\Application\Exception\InvalidAccountSnapshotChangeRequestException;
use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Application\Exception\NoAccountSnapshotChangesException;
use Source\Account\Account\Domain\Exception\AccountSetupUnavailableException;

interface RequestAccountSnapshotChangeInterface
{
    /**
     * @throws NoAccountSnapshotChangesException
     * @throws InvalidAccountSnapshotChangeRequestException
     * @throws AccountSnapshotChangeRequestForbiddenException
     * @throws AccountSnapshotNotFoundException
     * @throws AccountNotFoundException
     * @throws IncompleteAccountContactForCategoryChangeException
     * @throws AccountSetupUnavailableException
     * @throws InvalidDocumentsForVerificationException
     */
    public function process(RequestAccountSnapshotChangeInputPort $input, RequestAccountSnapshotChangeOutputPort $output): void;
}
