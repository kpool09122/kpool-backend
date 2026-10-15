<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\EventHandler;

use Source\Account\Account\Application\Service\AccountCategoryChangeNotificationServiceInterface;
use Source\Account\Account\Domain\Event\AccountCategoryChanged;

readonly class AccountCategoryChangedNotificationHandler
{
    public function __construct(
        private AccountCategoryChangeNotificationServiceInterface $accountCategoryChangeNotificationService,
    ) {
    }

    public function handle(AccountCategoryChanged $event): void
    {
        $this->accountCategoryChangeNotificationService->sendApprovalNotification(
            $event->accountIdentifier(),
            $event->newAccountCategory(),
        );
    }
}
