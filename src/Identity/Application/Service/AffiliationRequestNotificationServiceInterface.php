<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Shared\Domain\ValueObject\Email;

interface AffiliationRequestNotificationServiceInterface
{
    public function sendAffiliationRequestNotification(Email $targetEmail): void;
}
