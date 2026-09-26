<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Mail\PasskeyRecoveryCompletedMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryNotificationServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

class PasskeyRecoveryNotificationService implements PasskeyRecoveryNotificationServiceInterface
{
    public function notifyCompleted(Email $email, Language $language): void
    {
        DB::afterCommit(static fn () => Mail::to((string) $email)->send(new PasskeyRecoveryCompletedMail($language)));
    }
}
