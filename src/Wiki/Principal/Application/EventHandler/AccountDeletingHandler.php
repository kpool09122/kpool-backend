<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\EventHandler;

use Source\Account\Account\Domain\Event\AccountDeleting;
use Source\Wiki\Principal\Application\UseCase\Command\DeleteAccountData\DeleteAccountDataInput;
use Source\Wiki\Principal\Application\UseCase\Command\DeleteAccountData\DeleteAccountDataInterface;
use Source\Wiki\Principal\Application\UseCase\Command\DeleteAccountData\DeleteAccountDataOutput;

readonly class AccountDeletingHandler
{
    public function __construct(private DeleteAccountDataInterface $deleteAccountData)
    {
    }

    public function handle(AccountDeleting $event): void
    {
        $this->deleteAccountData->process(new DeleteAccountDataInput($event->accountIdentifier), new DeleteAccountDataOutput());
    }
}
