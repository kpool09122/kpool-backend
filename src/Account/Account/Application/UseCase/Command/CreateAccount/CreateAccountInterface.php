<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\CreateAccount;

use Source\Account\Account\Application\Exception\AccountAlreadyExistsException;

interface CreateAccountInterface
{
    /**
     * @param CreateAccountInputPort $input
     * @param CreateAccountOutputPort $output
     * @return void
     * @throws AccountAlreadyExistsException
     */
    public function process(CreateAccountInputPort $input, CreateAccountOutputPort $output): void;
}
