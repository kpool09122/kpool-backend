<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $principal_id
 * @property string $principal_group_id
 */
class PrincipalGroupMembership extends Model
{
    protected $table = 'site_management_principal_group_memberships';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected $casts = ['statements' => 'array'];
}
