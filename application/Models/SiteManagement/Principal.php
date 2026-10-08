<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Application\Models\Account\Account;
use Application\Models\Identity\Identity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string $id
 * @property string $account_id
 * @property string $identity_id
 * @property-read Account|null $account
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Identity|null $identity
 * @property-read Collection<int, PrincipalGroupMembership> $memberships
 */
#[Fillable([
    'id',
    'account_id',
    'identity_id',
])]
#[Table(name: 'site_management_principals', keyType: 'string')]
class Principal extends Model
{
    #[Override]
    public $incrementing = false;

    /** @return BelongsTo<Identity, $this> */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_id', 'id');
    }

    /** @return HasMany<PrincipalGroupMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(PrincipalGroupMembership::class, 'principal_id', 'id');
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }
}
