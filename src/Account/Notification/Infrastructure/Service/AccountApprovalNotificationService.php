<?php

declare(strict_types=1);

namespace Source\Account\Notification\Infrastructure\Service;

use Application\Mail\AffiliationApprovedMail;
use Application\Mail\DelegationApprovedMail;
use Closure;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Source\Account\Notification\Application\Service\AccountApprovalNotificationServiceInterface;
use Source\Shared\Application\Service\AccountNotificationRecipientServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Language;

readonly class AccountApprovalNotificationService implements AccountApprovalNotificationServiceInterface
{
    public function __construct(
        private AccountNotificationRecipientServiceInterface $accountNotificationRecipientService,
        private string $frontendBaseUrl,
    ) {
    }

    public function sendAffiliationApproval(AccountIdentifier $accountIdentifier, string $approverAccountName): void
    {
        $this->queue($accountIdentifier, fn (Language $language): Mailable => new AffiliationApprovedMail(
            rtrim($this->frontendBaseUrl, '/') . '/admin/account/affiliations',
            $approverAccountName,
            $language,
        ));
    }

    public function sendDelegationApproval(AccountIdentifier $accountIdentifier, string $approverAccountName): void
    {
        $this->queue($accountIdentifier, fn (Language $language): Mailable => new DelegationApprovedMail(
            rtrim($this->frontendBaseUrl, '/') . '/admin/account/delegations',
            $approverAccountName,
            $language,
        ));
    }

    /** @param Closure(Language): Mailable $createMail */
    private function queue(AccountIdentifier $accountIdentifier, Closure $createMail): void
    {
        $enqueue = function () use ($accountIdentifier, $createMail): void {
            foreach ($this->accountNotificationRecipientService->resolve([$accountIdentifier]) as $recipient) {
                Mail::to((string) $recipient->email())->queue($createMail($recipient->language()));
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($enqueue);

            return;
        }

        $enqueue();
    }
}
