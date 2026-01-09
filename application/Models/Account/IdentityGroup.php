<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $account_id
 * @property string $name
 * @property string $role
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
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

    public function members(): HasMany
    {
        return $this->hasMany(IdentityGroupMembership::class, 'identity_group_id', 'id');
    }
}
