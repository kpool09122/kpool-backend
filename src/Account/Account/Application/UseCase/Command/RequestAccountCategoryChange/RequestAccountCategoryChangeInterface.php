<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange;

use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;
use Source\Account\Account\Application\Exception\InvalidAccountCategoryChangeRequestException;
use Source\Account\Account\Application\Exception\SameAccountCategoryChangeRequestException;

interface RequestAccountCategoryChangeInterface
{
    /** @throws AccountNotFoundException
     * @throws AccountSnapshotNotFoundException
     * @throws AccountCategoryChangeRequestForbiddenException
     * @throws InvalidAccountCategoryChangeRequestException
     * @throws SameAccountCategoryChangeRequestException
     */
    public function process(RequestAccountCategoryChangeInputPort $input, RequestAccountCategoryChangeOutputPort $output): void;
}
