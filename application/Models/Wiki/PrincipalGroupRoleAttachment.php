<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $principal_group_id
 * @property string $role_id
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'principal_group_id',
    'role_id',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'principal_group_role_attachments')]
class PrincipalGroupRoleAttachment extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    public $timestamps = false;
}
