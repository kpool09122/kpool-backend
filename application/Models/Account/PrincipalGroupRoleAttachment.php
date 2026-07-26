<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $principal_group_id
 * @property string $role_id
 * @property-read Role|null $role
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'principal_group_id',
    'role_id',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'account_principal_group_role_attachments')]
class PrincipalGroupRoleAttachment extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }
}
