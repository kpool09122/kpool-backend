<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $role_id
 * @property string $policy_id
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'role_id',
    'policy_id',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'role_policy_attachments')]
class RolePolicyAttachment extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    public $timestamps = false;
}
