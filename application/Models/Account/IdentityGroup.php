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
 * @property-read Collection<int, IdentityGroupMembership> $members
 */
class IdentityGroup extends Model
{
    public $incrementing = false;

    protected $table = 'identity_groups';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'account_id',
        'name',
        'role',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<IdentityGroupMembership, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(IdentityGroupMembership::class, 'identity_group_id', 'id');
    }
}
