<?php

declare(strict_types=1);

namespace Source\Account\Notification\Application\EventHandler;

use Source\Account\Affiliation\Domain\Event\AffiliationApproved;
use Source\Account\Delegation\Domain\Event\DelegationApproved;
use Source\Account\Notification\Application\Service\AccountApprovalNotificationServiceInterface;

readonly class AccountRequestApprovedHandler
{
    public function __construct(
        private AccountApprovalNotificationServiceInterface $accountApprovalNotificationService,
    ) {
    }

    public function handleAffiliation(AffiliationApproved $event): void
    {
        $this->accountApprovalNotificationService->sendAffiliationApproval($event->requestedByAccountIdentifier, $event->approverAccountName);
    }

    public function handleDelegation(DelegationApproved $event): void
    {
        $this->accountApprovalNotificationService->sendDelegationApproval($event->requestedByAccountIdentifier, $event->approverAccountName);
    }
}
