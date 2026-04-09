<?php

declare(strict_types=1);

namespace Application\Models\Monetization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $monetization_account_id
 * @property string $interval
 * @property int $payout_delay_days
 * @property ?int $threshold_amount
 * @property ?string $threshold_currency
 * @property \Illuminate\Support\Carbon $next_closing_date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'monetization_account_id',
    'interval',
    'payout_delay_days',
    'threshold_amount',
    'threshold_currency',
    'next_closing_date',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'settlement_schedules', keyType: 'string')]
class SettlementSchedule extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected function casts(): array
    {
        return [
            'payout_delay_days' => 'integer',
            'threshold_amount' => 'integer',
            'next_closing_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function monetizationAccount(): BelongsTo
    {
        return $this->belongsTo(MonetizationAccount::class, 'monetization_account_id');
    }
}
