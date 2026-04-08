<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;

class CreatePayoutAccount
{
    /**
     * @param array{
     *     monetization_account_id?: string,
     *     stripe_external_account_id?: string,
     *     bank_name?: ?string,
     *     last4?: ?string,
     *     country?: ?string,
     *     currency?: ?string,
     *     account_holder_type?: ?string,
     *     is_default?: bool,
     *     status?: string,
     * } $overrides
     */
    public static function create(string $payoutAccountId, array $overrides = []): void
    {
        $monetizationAccountId = $overrides['monetization_account_id'] ?? StrTestHelper::generateUuid();

        // monetization_account_idが指定されていない場合は、FK制約を満たすためにMonetizationAccountを作成
        if (! isset($overrides['monetization_account_id'])) {
            CreateMonetizationAccount::create($monetizationAccountId);
        }

        DB::table('monetization_payout_accounts')->insert([
            'id' => $payoutAccountId,
            'monetization_account_id' => $monetizationAccountId,
            'stripe_external_account_id' => $overrides['stripe_external_account_id'] ?? 'ba_' . StrTestHelper::generateStr(20),
            'bank_name' => $overrides['bank_name'] ?? null,
            'last4' => $overrides['last4'] ?? null,
            'country' => $overrides['country'] ?? null,
            'currency' => $overrides['currency'] ?? null,
            'account_holder_type' => $overrides['account_holder_type'] ?? null,
            'is_default' => $overrides['is_default'] ?? false,
            'status' => $overrides['status'] ?? 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
