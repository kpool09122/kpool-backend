<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $role_id
 * @property string $policy_id
 * @property-read Policy|null $policy
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

    /**
     * @return BelongsTo<Policy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class, 'policy_id', 'id');
    }
}
