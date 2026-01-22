<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

interface CollaboratorNotificationServiceInterface
{
    public function sendDemotionWarning(Email $email, Language $language): void;

    public function sendPromotionNotification(Email $email, Language $language): void;

    public function sendDemotionNotification(Email $email, Language $language): void;
}
