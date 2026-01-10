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
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'principal_group_role_attachments';

    protected $fillable = [
        'principal_group_id',
        'role_id',
    ];
}
