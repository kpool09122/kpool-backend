<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Application\Models\Identity\Identity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $identity_id
 * @property string $account_id
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Identity|null $identity
 * @property-read Collection<int, PrincipalGroupMembership> $principalGroupMemberships
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'identity_id',
    'account_id',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'account_principals', keyType: 'string')]
class Principal extends Model
{
    public $incrementing = false;

    /** @return BelongsTo<Identity, $this> */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_id', 'id');
    }

    /** @return HasMany<PrincipalGroupMembership, $this> */
    public function principalGroupMemberships(): HasMany
    {
        return $this->hasMany(PrincipalGroupMembership::class, 'principal_id', 'id');
    }
}
