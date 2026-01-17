<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;

class CreateSettlementBatch
{
    /**
     * @param array{
     *     monetization_account_id?: string,
     *     currency?: string,
     *     gross_amount?: int,
     *     fee_amount?: int,
     *     net_amount?: int,
     *     period_start?: string,
     *     period_end?: string,
     *     status?: string,
     *     processed_at?: ?string,
     *     paid_at?: ?string,
     *     failed_at?: ?string,
     *     failure_reason?: ?string,
     * } $overrides
     */
    public static function create(string $settlementBatchId, array $overrides = []): void
    {
        $grossAmount = $overrides['gross_amount'] ?? 10000;
        $feeAmount = $overrides['fee_amount'] ?? 1000;
        $netAmount = $overrides['net_amount'] ?? ($grossAmount - $feeAmount);

        DB::table('settlement_batches')->insert([
            'id' => $settlementBatchId,
            'monetization_account_id' => $overrides['monetization_account_id'] ?? StrTestHelper::generateUuid(),
            'currency' => $overrides['currency'] ?? 'JPY',
            'gross_amount' => $grossAmount,
            'fee_amount' => $feeAmount,
            'net_amount' => $netAmount,
            'period_start' => $overrides['period_start'] ?? '2024-01-01',
            'period_end' => $overrides['period_end'] ?? '2024-01-31',
            'status' => $overrides['status'] ?? 'pending',
            'processed_at' => $overrides['processed_at'] ?? null,
            'paid_at' => $overrides['paid_at'] ?? null,
            'failed_at' => $overrides['failed_at'] ?? null,
            'failure_reason' => $overrides['failure_reason'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
