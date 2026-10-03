<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string $id
 * @property string $email
 * @property ?string $type
 * @property string $name
 * @property string $status
 * @property string $category
 * @property ?string $phone
 * @property ?string $address_country_code
 * @property ?string $address_administrative_area_code
 * @property ?string $address_postal_code
 * @property ?string $address_locality
 * @property ?string $address_line1
 * @property ?string $address_line2
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'id',
    'email',
    'type',
    'name',
    'status',
    'category',
    'phone',
    'address_country_code',
    'address_administrative_area_code',
    'address_postal_code',
    'address_locality',
    'address_line1',
    'address_line2',
])]
#[Table(name: 'accounts', keyType: 'string')]
class Account extends Model
{
    public $incrementing = false;

    #[Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** @return HasMany<AccountDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(AccountDocument::class, 'account_id', 'id');
    }
}
