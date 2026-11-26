<?php

declare(strict_types=1);

namespace Application\Models\Monetization;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $payment_method_id
 * @property string $monetization_account_id
 * @property string $external_payment_method_id
 * @property string $type
 * @property ?string $brand
 * @property ?string $last4
 * @property CarbonImmutable $archived_at
 */
class PaymentMethodArchive extends Model
{
    protected $table = 'monetization_payment_method_archives';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return ['archived_at' => 'immutable_datetime'];
    }
}
