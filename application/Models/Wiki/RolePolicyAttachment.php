<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $role_id
 * @property string $policy_id
 */
class RolePolicyAttachment extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'role_policy_attachments';

    protected $fillable = [
        'role_id',
        'policy_id',
    ];
}
