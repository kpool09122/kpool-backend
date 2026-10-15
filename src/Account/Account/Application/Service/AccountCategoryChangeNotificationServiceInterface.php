<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Service;

use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountCategoryChangeNotificationServiceInterface
{
    public function sendApprovalNotification(AccountIdentifier $accountIdentifier, AccountCategory $category): void;
}
