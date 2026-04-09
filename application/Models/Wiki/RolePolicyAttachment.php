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
    #[\Override]
    public $incrementing = false;

    #[\Override]
    public $timestamps = false;

    #[\Override]
    protected $table = 'role_policy_attachments';

    #[\Override]
    protected $fillable = [
        'role_id',
        'policy_id',
    ];
}
