<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $role_id
 * @property string $policy_id
 * @property-read Policy|null $policy
 */
#[Fillable([
    'role_id',
    'policy_id',
])]
#[Table(name: 'account_role_policy_attachments')]
class RolePolicyAttachment extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    /**
     * @return BelongsTo<Policy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class, 'policy_id', 'id');
    }
}
