<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;

class CreatePaymentMethod
{
    /**
     * @param array{
     *     monetization_account_id?: string,
     *     external_payment_method_id?: string,
     *     type?: string,
     *     brand?: ?string,
     *     last4?: ?string,
     *     exp_month?: ?int,
     *     exp_year?: ?int,
     *     is_default?: bool,
     *     status?: string,
     * } $overrides
     */
    public static function create(string $paymentMethodId, array $overrides = []): void
    {
        $monetizationAccountId = $overrides['monetization_account_id'] ?? StrTestHelper::generateUuid();

        // monetization_account_idが指定されていない場合は、FK制約を満たすためにMonetizationAccountを作成
        if (! isset($overrides['monetization_account_id'])) {
            CreateMonetizationAccount::create($monetizationAccountId);
        }

        DB::table('monetization_payment_methods')->insert([
            'id' => $paymentMethodId,
            'monetization_account_id' => $monetizationAccountId,
            'external_payment_method_id' => $overrides['external_payment_method_id'] ?? 'pm_' . StrTestHelper::generateStr(20),
            'type' => $overrides['type'] ?? 'card',
            'brand' => $overrides['brand'] ?? null,
            'last4' => $overrides['last4'] ?? null,
            'exp_month' => $overrides['exp_month'] ?? null,
            'exp_year' => $overrides['exp_year'] ?? null,
            'is_default' => $overrides['is_default'] ?? false,
            'status' => $overrides['status'] ?? 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
