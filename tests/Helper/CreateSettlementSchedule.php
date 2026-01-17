<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;

class CreateSettlementSchedule
{
    /**
     * @param array{
     *     monetization_account_id?: string,
     *     interval?: string,
     *     payout_delay_days?: int,
     *     threshold_amount?: ?int,
     *     threshold_currency?: ?string,
     *     next_closing_date?: string,
     * } $overrides
     */
    public static function create(string $settlementScheduleId, array $overrides = []): void
    {
        DB::table('settlement_schedules')->insert([
            'id' => $settlementScheduleId,
            'monetization_account_id' => $overrides['monetization_account_id'] ?? StrTestHelper::generateUuid(),
            'interval' => $overrides['interval'] ?? 'monthly',
            'payout_delay_days' => $overrides['payout_delay_days'] ?? 0,
            'threshold_amount' => $overrides['threshold_amount'] ?? null,
            'threshold_currency' => $overrides['threshold_currency'] ?? null,
            'next_closing_date' => $overrides['next_closing_date'] ?? '2024-01-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
