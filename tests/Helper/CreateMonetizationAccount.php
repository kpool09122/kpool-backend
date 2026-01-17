<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;

class CreateMonetizationAccount
{
    /**
     * @param array{
     *     account_id?: string,
     *     capabilities?: string,
     *     stripe_customer_id?: ?string,
     *     stripe_connected_account_id?: ?string,
     * } $overrides
     */
    public static function create(string $monetizationAccountId, array $overrides = []): void
    {
        $accountId = $overrides['account_id'] ?? StrTestHelper::generateUuid();

        // account_idが指定されていない場合は、FK制約を満たすためにAccountを作成
        if (! isset($overrides['account_id'])) {
            CreateAccount::create($accountId);
        }

        DB::table('monetization_accounts')->insert([
            'id' => $monetizationAccountId,
            'account_id' => $accountId,
            'capabilities' => $overrides['capabilities'] ?? '["purchase"]',
            'stripe_customer_id' => $overrides['stripe_customer_id'] ?? null,
            'stripe_connected_account_id' => $overrides['stripe_connected_account_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
