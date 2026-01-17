<?php

declare(strict_types=1);

namespace Application\Models\Monetization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $id
 * @property string $monetization_account_id
 * @property string $currency
 * @property int $gross_amount
 * @property int $fee_amount
 * @property int $net_amount
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property string $status
 * @property ?\Illuminate\Support\Carbon $processed_at
 * @property ?\Illuminate\Support\Carbon $paid_at
 * @property ?\Illuminate\Support\Carbon $failed_at
 * @property ?string $failure_reason
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class SettlementBatch extends Model
{
    public $incrementing = false;

    protected $table = 'settlement_batches';

    protected $keyType = 'string';

    protected $fillable = [
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
    ];

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
}
