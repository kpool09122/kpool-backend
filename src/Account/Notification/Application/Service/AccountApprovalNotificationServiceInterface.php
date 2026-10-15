<?php

declare(strict_types=1);

namespace Source\Account\Notification\Application\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountApprovalNotificationServiceInterface
{
    public function sendAffiliationApproval(AccountIdentifier $accountIdentifier, string $approverAccountName): void;

    public function sendDelegationApproval(AccountIdentifier $accountIdentifier, string $approverAccountName): void;
}
