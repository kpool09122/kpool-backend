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
 * @property string $monetization_account_id
 * @property string $external_payment_method_id
 * @property string $type
 * @property ?string $brand
 * @property ?string $last4
 * @property ?int $exp_month
 * @property ?int $exp_year
 * @property bool $is_default
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'id',
    'monetization_account_id',
    'external_payment_method_id',
    'type',
    'brand',
    'last4',
    'exp_month',
    'exp_year',
    'is_default',
    'status',
])]
#[Table(name: 'monetization_payment_methods', keyType: 'string')]
class PaymentMethod extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    protected function casts(): array
    {
        return [
            'exp_month' => 'integer',
            'exp_year' => 'integer',
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
