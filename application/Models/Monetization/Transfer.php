<?php

declare(strict_types=1);

namespace Application\Models\Monetization;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string $id
 * @property string $settlement_batch_id
 * @property string $monetization_account_id
 * @property string $currency
 * @property int $amount
 * @property string $status
 * @property ?Carbon $sent_at
 * @property ?Carbon $failed_at
 * @property ?string $failure_reason
 * @property ?string $stripe_transfer_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
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
])]
#[Table(name: 'transfers', keyType: 'string')]
class Transfer extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
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
