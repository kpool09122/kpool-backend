<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $account_id
 * @property string $name
 * @property bool $is_default
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class PrincipalGroup extends Model
{
    public $incrementing = false;

    protected $table = 'principal_groups';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'account_id',
        'name',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return HasMany<PrincipalGroupMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(PrincipalGroupMembership::class, 'principal_group_id', 'id');
    }
}
