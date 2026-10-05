<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $role_id
 * @property string $policy_id
 */
class RolePolicyAttachment extends Model
{
    protected $table = 'site_management_role_policy_attachments';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected $casts = ['statements' => 'array'];
}
