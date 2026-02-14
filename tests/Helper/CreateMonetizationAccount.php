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
     *     billing_address?: ?array<string, mixed>,
     *     billing_contact?: ?array<string, mixed>,
     *     billing_method?: ?string,
     *     tax_info?: ?array<string, mixed>,
     *     card_meta?: ?array<string, mixed>,
     *     payout_bank_meta?: ?array<string, mixed>,
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
            'billing_address' => isset($overrides['billing_address']) ? json_encode($overrides['billing_address']) : null,
            'billing_contact' => isset($overrides['billing_contact']) ? json_encode($overrides['billing_contact']) : null,
            'billing_method' => $overrides['billing_method'] ?? null,
            'tax_info' => isset($overrides['tax_info']) ? json_encode($overrides['tax_info']) : null,
            'card_meta' => isset($overrides['card_meta']) ? json_encode($overrides['card_meta']) : null,
            'payout_bank_meta' => isset($overrides['payout_bank_meta']) ? json_encode($overrides['payout_bank_meta']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
