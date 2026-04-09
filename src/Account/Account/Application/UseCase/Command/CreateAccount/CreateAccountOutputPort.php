<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\CreateAccount;

use Source\Account\Account\Domain\Entity\Account;

interface CreateAccountOutputPort
{
    public function setAccount(Account $account): void;
}
