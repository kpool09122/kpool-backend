<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetAccount;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Account\Application\UseCase\Query\AccountReadModel;

interface GetAccountInterface
{
    /**
     * @throws AccountNotFoundException
     * @throws AccountUpdateForbiddenException
     */
    public function process(GetAccountInputPort $input): AccountReadModel;
}
