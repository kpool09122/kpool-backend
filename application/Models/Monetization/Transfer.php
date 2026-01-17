<?php

declare(strict_types=1);

namespace Application\Models\Monetization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $settlement_batch_id
 * @property string $monetization_account_id
 * @property string $currency
 * @property int $amount
 * @property string $status
 * @property ?\Illuminate\Support\Carbon $sent_at
 * @property ?\Illuminate\Support\Carbon $failed_at
 * @property ?string $failure_reason
 * @property ?string $stripe_transfer_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Transfer extends Model
{
    public $incrementing = false;

    protected $table = 'transfers';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'settlement_batch_id',
        'monetization_account_id',
        'currency',
        'amount',
        'status',
        'sent_at',
        'failed_at',
        'failure_reason',
        'stripe_transfer_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function settlementBatch(): BelongsTo
    {
        return $this->belongsTo(SettlementBatch::class, 'settlement_batch_id');
    }

    public function monetizationAccount(): BelongsTo
    {
        return $this->belongsTo(MonetizationAccount::class, 'monetization_account_id');
    }
}
