<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $principal_group_id
 * @property string $role_id
 */
class PrincipalGroupRoleAttachment extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    public $timestamps = false;

    #[\Override]
    protected $table = 'principal_group_role_attachments';

    #[\Override]
    protected $fillable = [
        'principal_group_id',
        'role_id',
    ];
}
