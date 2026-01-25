<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Mail\CollaboratorDemotedMail;
use Application\Mail\CollaboratorPromotedMail;
use Application\Mail\DemotionWarningMail;
use Illuminate\Support\Facades\Mail;
use Source\Identity\Application\Service\CollaboratorNotificationServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

readonly class CollaboratorNotificationService implements CollaboratorNotificationServiceInterface
{
    public function sendDemotionWarning(Email $email, Language $language): void
    {
        Mail::to((string) $email)->send(
            new DemotionWarningMail($language),
        );
    }

    public function sendPromotionNotification(Email $email, Language $language): void
    {
        Mail::to((string) $email)->send(
            new CollaboratorPromotedMail($language),
        );
    }

    public function sendDemotionNotification(Email $email, Language $language): void
    {
        Mail::to((string) $email)->send(
            new CollaboratorDemotedMail($language),
        );
    }
}
