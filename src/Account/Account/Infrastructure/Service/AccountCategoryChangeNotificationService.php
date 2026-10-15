<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Service;

use Application\Mail\AccountCategoryChangeApprovedMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Source\Account\Account\Application\Service\AccountCategoryChangeNotificationServiceInterface;
use Source\Shared\Application\Service\AccountNotificationRecipientServiceInterface;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class AccountCategoryChangeNotificationService implements AccountCategoryChangeNotificationServiceInterface
{
    public function __construct(
        private AccountNotificationRecipientServiceInterface $accountNotificationRecipientService,
        private string $frontendBaseUrl,
    ) {
    }

    public function sendApprovalNotification(AccountIdentifier $accountIdentifier, AccountCategory $category): void
    {
        $enqueue = function () use ($accountIdentifier, $category): void {
            foreach ($this->accountNotificationRecipientService->resolve([$accountIdentifier]) as $recipient) {
                Mail::to((string) $recipient->email())->queue(new AccountCategoryChangeApprovedMail(
                    rtrim($this->frontendBaseUrl, '/') . '/admin/account/category-change',
                    $category,
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
}
