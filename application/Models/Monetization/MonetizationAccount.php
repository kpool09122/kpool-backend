<?php

declare(strict_types=1);

namespace Application\Models\Monetization;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $account_id
 * @property string $capabilities
 * @property ?string $stripe_customer_id
 * @property ?string $stripe_connected_account_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class MonetizationAccount extends Model
{
    public $incrementing = false;

    protected $table = 'monetization_accounts';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'account_id',
        'capabilities',
        'stripe_customer_id',
        'stripe_connected_account_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
