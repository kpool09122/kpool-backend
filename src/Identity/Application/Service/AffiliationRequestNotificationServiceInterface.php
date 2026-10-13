<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AffiliationRequestNotificationServiceInterface
{
    public function sendAffiliationRequestNotification(AccountIdentifier $targetAccountIdentifier): void;
}
