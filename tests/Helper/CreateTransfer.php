<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;

class CreateTransfer
{
    /**
     * @param array{
     *     settlement_batch_id?: string,
     *     monetization_account_id?: string,
     *     currency?: string,
     *     amount?: int,
     *     status?: string,
     *     sent_at?: ?string,
     *     failed_at?: ?string,
     *     failure_reason?: ?string,
     *     stripe_transfer_id?: ?string,
     * } $overrides
     */
    public static function create(string $transferId, array $overrides = []): void
    {
        DB::table('transfers')->insert([
            'id' => $transferId,
            'settlement_batch_id' => $overrides['settlement_batch_id'] ?? StrTestHelper::generateUuid(),
            'monetization_account_id' => $overrides['monetization_account_id'] ?? StrTestHelper::generateUuid(),
            'currency' => $overrides['currency'] ?? 'JPY',
            'amount' => $overrides['amount'] ?? 10000,
            'status' => $overrides['status'] ?? 'pending',
            'sent_at' => $overrides['sent_at'] ?? null,
            'failed_at' => $overrides['failed_at'] ?? null,
            'failure_reason' => $overrides['failure_reason'] ?? null,
            'stripe_transfer_id' => $overrides['stripe_transfer_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
