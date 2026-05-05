<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\EventHandler;

use Application\Jobs\SendAccountAuthCodeJob;
use Source\Account\Account\Domain\Event\AccountCreated;

readonly class AccountCreatedHandler
{
    public function handle(AccountCreated $event): void
    {
        if ($event->identityIdentifier !== null) {
            return;
        }

        SendAccountAuthCodeJob::dispatch($event->email, $event->language)->afterCommit();
    }
}
