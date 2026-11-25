<?php

declare(strict_types=1);

namespace Application\Models\Monetization;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string $id
 * @property string $order_id
 * @property string $buyer_monetization_account_id
 * @property string $currency
 * @property int $amount
 * @property string $payment_method_id
 * @property Carbon $created_at
 * @property string $status
 * @property ?Carbon $authorized_at
 * @property ?Carbon $captured_at
 * @property ?Carbon $failed_at
 * @property ?string $failure_reason
 * @property int $refunded_amount
 * @property ?Carbon $last_refunded_at
 * @property ?string $last_refund_reason
 * @property ?string $stripe_payment_intent_id
 */
#[Fillable([
    'id',
    'order_id',
    'buyer_monetization_account_id',
    'currency',
    'amount',
    'payment_method_id',
    'created_at',
    'status',
    'authorized_at',
    'captured_at',
    'failed_at',
    'failure_reason',
    'refunded_amount',
    'last_refunded_at',
    'last_refund_reason',
    'stripe_payment_intent_id',
])]
#[Table(name: 'payments', keyType: 'string')]
class Payment extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    public $timestamps = false;

    #[Override]
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'created_at' => 'datetime',
            'authorized_at' => 'datetime',
            'captured_at' => 'datetime',
            'failed_at' => 'datetime',
            'refunded_amount' => 'integer',
            'last_refunded_at' => 'datetime',
        ];
    }
}
