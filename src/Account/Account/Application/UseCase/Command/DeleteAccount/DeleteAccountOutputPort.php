<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\DeleteAccount;

use Source\Account\Account\Domain\Entity\Account;

interface DeleteAccountOutputPort
{
    public function setAccount(Account $account): void;
}
