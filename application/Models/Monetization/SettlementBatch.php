<?php

declare(strict_types=1);

namespace Application\Models\Monetization;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string $id
 * @property string $monetization_account_id
 * @property string $currency
 * @property int $gross_amount
 * @property int $fee_amount
 * @property int $net_amount
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property string $status
 * @property ?Carbon $processed_at
 * @property ?Carbon $paid_at
 * @property ?Carbon $failed_at
 * @property ?string $failure_reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'id',
    'monetization_account_id',
    'currency',
    'gross_amount',
    'fee_amount',
    'net_amount',
    'period_start',
    'period_end',
    'status',
    'processed_at',
    'paid_at',
    'failed_at',
    'failure_reason',
])]
#[Table(name: 'settlement_batches', keyType: 'string')]
class SettlementBatch extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    protected function casts(): array
    {
        return [
            'gross_amount' => 'integer',
            'fee_amount' => 'integer',
            'net_amount' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'processed_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function monetizationAccount(): BelongsTo
    {
        return $this->belongsTo(MonetizationAccount::class, 'monetization_account_id');
    }

    public function transfer(): HasOne
    {
        return $this->hasOne(Transfer::class, 'settlement_batch_id');
    }

    public function settlementSchedule(): HasOne
    {
        return $this->hasOne(SettlementSchedule::class, 'monetization_account_id', 'monetization_account_id');
    }
}
