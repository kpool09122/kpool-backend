<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\EventHandler;

use Application\Jobs\SendAccountConflictNotificationJob;
use Source\Account\Account\Domain\Event\AccountCreationConflicted;

readonly class AccountCreationConflictedHandler
{
    public function handle(AccountCreationConflicted $event): void
    {
        SendAccountConflictNotificationJob::dispatch($event->email, $event->language)->afterCommit();
    }
}
