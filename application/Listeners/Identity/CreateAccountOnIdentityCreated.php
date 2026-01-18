<?php

declare(strict_types=1);

namespace Application\Listeners\Identity;

use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInput;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInterface;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Identity\Domain\Event\IdentityCreated;

readonly class CreateAccountOnIdentityCreated
{
    public function __construct(
        private CreateAccountInterface $createAccount,
    ) {
    }

    public function handle(IdentityCreated $event): void
    {
        $this->createAccount->process(new CreateAccountInput(
            email: $event->email,
            accountType: $event->accountType,
            accountName: new AccountName($event->name ?? 'My Account'),
            identityIdentifier: $event->identityIdentifier,
        ));
    }
}
