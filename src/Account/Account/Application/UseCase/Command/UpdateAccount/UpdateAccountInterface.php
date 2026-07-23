<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\UpdateAccount;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;

interface UpdateAccountInterface
{
    /**
     * @param UpdateAccountInputPort $input
     * @param UpdateAccountOutputPort $output
     * @return void
     * @throws AccountNotFoundException
     * @throws AccountUpdateForbiddenException
     */
    public function process(UpdateAccountInputPort $input, UpdateAccountOutputPort $output): void;
}
