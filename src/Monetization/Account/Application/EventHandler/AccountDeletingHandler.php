<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\EventHandler;

use Source\Account\Account\Domain\Event\AccountDeleting;
use Source\Monetization\Account\Application\Service\AccountDeletionServiceInterface;

readonly class AccountDeletingHandler
{
    public function __construct(private AccountDeletionServiceInterface $accountDeletionService)
    {
    }

    public function handle(AccountDeleting $event): void
    {
        $this->accountDeletionService->delete($event->accountIdentifier);
    }
}
