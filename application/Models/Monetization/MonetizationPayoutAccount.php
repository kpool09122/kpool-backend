<?php

declare(strict_types=1);

namespace Application\Models\Monetization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $monetization_account_id
 * @property string $stripe_external_account_id
 * @property ?string $bank_name
 * @property ?string $last4
 * @property ?string $country
 * @property ?string $currency
 * @property ?string $account_holder_type
 * @property bool $is_default
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MonetizationPayoutAccount extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected $table = 'monetization_payout_accounts';

    #[\Override]
    protected $keyType = 'string';

    #[\Override]
    protected $fillable = [
        'id',
        'monetization_account_id',
        'stripe_external_account_id',
        'bank_name',
        'last4',
        'country',
        'currency',
        'account_holder_type',
        'is_default',
        'status',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MonetizationAccount, $this>
     */
    public function monetizationAccount(): BelongsTo
    {
        return $this->belongsTo(MonetizationAccount::class, 'monetization_account_id');
    }
}
