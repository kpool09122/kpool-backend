<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $principal_group_id
 * @property string $principal_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'principal_group_id',
    'principal_id',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'account_principal_group_memberships', keyType: 'string')]
class PrincipalGroupMembership extends Model
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

    public function principalGroup(): BelongsTo
    {
        return $this->belongsTo(PrincipalGroup::class, 'principal_group_id', 'id');
    }
}
