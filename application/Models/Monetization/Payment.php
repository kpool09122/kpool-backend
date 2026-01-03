<?php

declare(strict_types=1);

namespace Application\Models\Monetization;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $order_id
 * @property string $buyer_monetization_account_id
 * @property string $currency
 * @property int $amount
 * @property string $payment_method_id
 * @property string $payment_method_type
 * @property string $payment_method_label
 * @property bool $payment_method_recurring_enabled
 * @property \Illuminate\Support\Carbon $created_at
 * @property string $status
 * @property ?\Illuminate\Support\Carbon $authorized_at
 * @property ?\Illuminate\Support\Carbon $captured_at
 * @property ?\Illuminate\Support\Carbon $failed_at
 * @property ?string $failure_reason
 * @property int $refunded_amount
 * @property ?\Illuminate\Support\Carbon $last_refunded_at
 * @property ?string $last_refund_reason
 */
class Payment extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'payments';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'order_id',
        'buyer_monetization_account_id',
        'currency',
        'amount',
        'payment_method_id',
        'payment_method_type',
        'payment_method_label',
        'payment_method_recurring_enabled',
        'created_at',
        'status',
        'authorized_at',
        'captured_at',
        'failed_at',
        'failure_reason',
        'refunded_amount',
        'last_refunded_at',
        'last_refund_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'payment_method_recurring_enabled' => 'boolean',
            'created_at' => 'datetime',
            'authorized_at' => 'datetime',
            'captured_at' => 'datetime',
            'failed_at' => 'datetime',
            'refunded_amount' => 'integer',
            'last_refunded_at' => 'datetime',
        ];
    }
}
