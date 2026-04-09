<?php

declare(strict_types=1);

namespace Application\Models\Monetization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $account_id
 * @property string $capabilities
 * @property ?string $stripe_customer_id
 * @property ?string $stripe_connected_account_id
 * @property ?array $billing_address
 * @property ?array $billing_contact
 * @property ?string $billing_method
 * @property ?array $tax_info
 * @property ?array $card_meta
 * @property ?array $payout_bank_meta
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'account_id',
    'capabilities',
    'stripe_customer_id',
    'stripe_connected_account_id',
    'billing_address',
    'billing_contact',
    'billing_method',
    'tax_info',
    'card_meta',
    'payout_bank_meta',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'monetization_accounts', keyType: 'string')]
class MonetizationAccount extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected function casts(): array
    {
        return [
            'billing_address' => 'array',
            'billing_contact' => 'array',
            'tax_info' => 'array',
            'card_meta' => 'array',
            'payout_bank_meta' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<MonetizationPaymentMethod, $this>
     */
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(MonetizationPaymentMethod::class, 'monetization_account_id');
    }

    /**
     * @return HasMany<MonetizationPayoutAccount, $this>
     */
    public function payoutAccounts(): HasMany
    {
        return $this->hasMany(MonetizationPayoutAccount::class, 'monetization_account_id');
    }
}
