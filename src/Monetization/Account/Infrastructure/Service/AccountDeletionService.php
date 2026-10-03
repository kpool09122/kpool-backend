<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use Source\Monetization\Account\Application\Service\AccountDeletionServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class AccountDeletionService implements AccountDeletionServiceInterface
{
    public function delete(AccountIdentifier $accountIdentifier): void
    {
        $monetizationAccountIds = DB::table('monetization_accounts')
            ->where('account_id', (string) $accountIdentifier)->lockForUpdate()->pluck('id');
        // These billing records hold plain IDs rather than foreign keys.
        // Invoice lines cascade; all other account-owned financial rows cascade from Account.
        DB::table('invoices')->whereIn('buyer_monetization_account_id', $monetizationAccountIds)->delete();
        DB::table('payments')->whereIn('buyer_monetization_account_id', $monetizationAccountIds)->delete();
    }
}
