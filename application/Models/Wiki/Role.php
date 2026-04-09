<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property bool $is_system_role
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Collection<int, RolePolicyAttachment> $policyAttachments
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'name',
    'is_system_role',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'roles', keyType: 'string')]
class Role extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected function casts(): array
    {
        return [
            'is_system_role' => 'boolean',
        ];
    }

    /**
     * @return HasMany<RolePolicyAttachment, $this>
     */
    public function policyAttachments(): HasMany
    {
        return $this->hasMany(RolePolicyAttachment::class, 'role_id', 'id');
    }
}
