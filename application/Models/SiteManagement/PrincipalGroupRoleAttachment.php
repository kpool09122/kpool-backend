<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $principal_group_id
 * @property string $role_id
 */
class PrincipalGroupRoleAttachment extends Model
{
    protected $table = 'site_management_principal_group_role_attachments';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected $casts = ['statements' => 'array'];
}
