<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Mail\AffiliationRequestMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Source\Identity\Application\Service\AffiliationRequestNotificationServiceInterface;
use Source\Shared\Application\Service\AccountNotificationRecipientServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class AffiliationRequestNotificationService implements AffiliationRequestNotificationServiceInterface
{
    public function __construct(
        private AccountNotificationRecipientServiceInterface $accountNotificationRecipientService,
        private string $frontendBaseUrl,
    ) {
    }

    public function sendAffiliationRequestNotification(AccountIdentifier $accountIdentifier): void
    {
        $enqueue = function () use ($accountIdentifier): void {
            foreach ($this->accountNotificationRecipientService->resolve([$accountIdentifier]) as $recipient) {
                Mail::to((string) $recipient->email())->queue(new AffiliationRequestMail(
                    $this->buildAffiliationUrl(),
                    $recipient->language(),
                ));
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($enqueue);

            return;
        }

        $enqueue();
    }

    private function buildAffiliationUrl(): string
    {
        return rtrim($this->frontendBaseUrl, '/') . '/admin/account/affiliations';
    }
}
