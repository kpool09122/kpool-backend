<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $identity_group_id
 * @property string $identity_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'identity_group_id',
    'identity_id',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'identity_group_memberships', keyType: 'string')]
class IdentityGroupMembership extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function identityGroup(): BelongsTo
    {
        return $this->belongsTo(IdentityGroup::class, 'identity_group_id', 'id');
    }
}
