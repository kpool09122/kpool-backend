<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Mail\PasskeyRecoveryCompletedMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryNotificationServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Throwable;

readonly class PasskeyRecoveryNotificationService implements PasskeyRecoveryNotificationServiceInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function notifyCompleted(Email $email, Language $language): void
    {
        $enqueue = function () use ($email, $language): void {
            try {
                Mail::to((string) $email)->queue(new PasskeyRecoveryCompletedMail($language)->onConnection('passkey_recovery'));
            } catch (Throwable $exception) {
                $this->logger->error('Failed to queue passkey recovery completion email.', ['exception' => $exception]);
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($enqueue);

            return;
        }

        $enqueue();
    }
}
