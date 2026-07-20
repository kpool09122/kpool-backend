<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $account_id
 * @property string $name
 * @property string $role
 * @property bool $is_default
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, PrincipalGroupMembership> $members
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'account_id',
    'name',
    'role',
    'is_default',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'account_principal_groups', keyType: 'string')]
class PrincipalGroup extends Model
{
    #[\Override]
    public $incrementing = false;

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
     * @return HasMany<PrincipalGroupMembership, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(PrincipalGroupMembership::class, 'principal_group_id', 'id');
    }
}
