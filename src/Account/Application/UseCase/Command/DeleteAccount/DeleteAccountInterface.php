<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\DeleteAccount;

use Source\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\Exception\AccountDeletionBlockedException;

interface DeleteAccountInterface
{
    /**
     * @param DeleteAccountInputPort $input
     * @return Account
     * @throws AccountNotFoundException
     * @throws AccountDeletionBlockedException
     */
    public function process(DeleteAccountInputPort $input): Account;
}
