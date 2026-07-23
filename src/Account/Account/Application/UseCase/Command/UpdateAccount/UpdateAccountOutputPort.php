<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\UpdateAccount;

use Source\Account\Account\Domain\Entity\Account;

interface UpdateAccountOutputPort
{
    public function setAccount(Account $account): void;
}
