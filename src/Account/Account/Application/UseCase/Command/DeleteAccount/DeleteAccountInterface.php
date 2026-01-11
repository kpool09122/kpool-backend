<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\DeleteAccount;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Exception\AccountDeletionBlockedException;

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
