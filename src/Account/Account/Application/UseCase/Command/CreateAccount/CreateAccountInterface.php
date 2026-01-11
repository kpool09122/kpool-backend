<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\CreateAccount;

use Source\Account\Account\Application\Exception\AccountAlreadyExistsException;
use Source\Account\Account\Domain\Entity\Account;

interface CreateAccountInterface
{
    /**
     * @param CreateAccountInputPort $input
     * @return Account
     * @throws AccountAlreadyExistsException
     */
    public function process(CreateAccountInputPort $input): Account;
}
