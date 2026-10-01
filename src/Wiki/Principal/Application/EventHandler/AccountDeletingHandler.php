<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\EventHandler;

use Source\Account\Account\Domain\Event\AccountDeleting;
use Source\Wiki\Principal\Application\Service\IdentityWithdrawalServiceInterface;

readonly class AccountDeletingHandler
{
    public function __construct(private IdentityWithdrawalServiceInterface $identityWithdrawalService)
    {
    }

    public function handle(AccountDeleting $event): void
    {
        $this->identityWithdrawalService->deleteAccountData($event->accountIdentifier);
    }
}
