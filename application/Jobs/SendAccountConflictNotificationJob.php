<?php

declare(strict_types=1);

namespace Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Source\Identity\Domain\Service\AuthCodeServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Throwable;

class SendAccountConflictNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private readonly Email $email,
        private readonly Language $language,
    ) {
    }

    public function handle(AuthCodeServiceInterface $authCodeService): void
    {
        Log::info('SendAccountConflictNotificationJob started', [
            'email' => (string) $this->email,
        ]);

        $authCodeService->notifyConflict($this->email, $this->language);

        Log::info('SendAccountConflictNotificationJob completed', [
            'email' => (string) $this->email,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SendAccountConflictNotificationJob failed permanently', [
            'email' => (string) $this->email,
            'exception' => $exception->getMessage(),
        ]);
    }
}
