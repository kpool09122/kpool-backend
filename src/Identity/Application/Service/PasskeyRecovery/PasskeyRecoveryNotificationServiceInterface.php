<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\PasskeyRecovery;

use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

interface PasskeyRecoveryNotificationServiceInterface
{
    public function notifyCompleted(Email $email, Language $language): void;
}
