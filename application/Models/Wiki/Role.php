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
class Role extends Model
{
    public $incrementing = false;

    protected $table = 'roles';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'is_system_role',
    ];

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
