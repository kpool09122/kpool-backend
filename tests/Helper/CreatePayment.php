<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;

class CreatePayment
{
    /**
     * @param array{
     *     order_id?: string,
     *     buyer_monetization_account_id?: string,
     *     currency?: string,
     *     amount?: int,
     *     payment_method_id?: string,
     *     payment_method_type?: string,
     *     payment_method_label?: string,
     *     payment_method_recurring_enabled?: bool,
     *     status?: string,
     *     authorized_at?: ?string,
     *     captured_at?: ?string,
     *     failed_at?: ?string,
     *     failure_reason?: ?string,
     *     refunded_amount?: int,
     *     last_refunded_at?: ?string,
     *     last_refund_reason?: ?string,
     *     stripe_payment_intent_id?: ?string,
     *     stripe_payment_method_id?: ?string,
     * } $overrides
     */
    public static function create(string $paymentId, array $overrides = []): void
    {
        DB::table('payments')->insert([
            'id' => $paymentId,
            'order_id' => $overrides['order_id'] ?? StrTestHelper::generateUuid(),
            'buyer_monetization_account_id' => $overrides['buyer_monetization_account_id'] ?? StrTestHelper::generateUuid(),
            'currency' => $overrides['currency'] ?? 'JPY',
            'amount' => $overrides['amount'] ?? 1000,
            'payment_method_id' => $overrides['payment_method_id'] ?? StrTestHelper::generateUuid(),
            'payment_method_type' => $overrides['payment_method_type'] ?? 'card',
            'payment_method_label' => $overrides['payment_method_label'] ?? 'Visa **** 4242',
            'payment_method_recurring_enabled' => $overrides['payment_method_recurring_enabled'] ?? true,
            'created_at' => now(),
            'status' => $overrides['status'] ?? 'pending',
            'authorized_at' => $overrides['authorized_at'] ?? null,
            'captured_at' => $overrides['captured_at'] ?? null,
            'failed_at' => $overrides['failed_at'] ?? null,
            'failure_reason' => $overrides['failure_reason'] ?? null,
            'refunded_amount' => $overrides['refunded_amount'] ?? 0,
            'last_refunded_at' => $overrides['last_refunded_at'] ?? null,
            'last_refund_reason' => $overrides['last_refund_reason'] ?? null,
            'stripe_payment_intent_id' => $overrides['stripe_payment_intent_id'] ?? null,
            'stripe_payment_method_id' => $overrides['stripe_payment_method_id'] ?? null,
        ]);
    }
}
