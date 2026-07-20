<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $role
 * @property string $policy_id
 * @property-read AccountPolicy|null $policy
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'role',
    'policy_id',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'account_role_policy_attachments')]
class AccountRolePolicyAttachment extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    /**
     * @return BelongsTo<AccountPolicy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(AccountPolicy::class, 'policy_id', 'id');
    }
}
