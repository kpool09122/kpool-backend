<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property string $role_id
 * @property string $policy_id
 * @property-read Policy|null $policy
 */
#[Fillable([
    'role_id',
    'policy_id',
])]
#[Table(name: 'site_management_role_policy_attachments', keyType: 'string')]
class RolePolicyAttachment extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    public $timestamps = false;

    /** @var string[] */
    protected $primaryKey = ['role_id', 'policy_id'];

    #[Override]
    public function getKey(): string
    {
        return $this->role_id . '_' . $this->policy_id;
    }

    #[Override]
    protected function setKeysForSelectQuery($query): mixed
    {
        return $query->where('role_id', $this->getRawOriginal('role_id', $this->role_id))
            ->where('policy_id', $this->getRawOriginal('policy_id', $this->policy_id));
    }

    #[Override]
    protected function setKeysForSaveQuery($query): mixed
    {
        return $this->setKeysForSelectQuery($query);
    }

    /** @return BelongsTo<Policy, $this> */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class, 'policy_id', 'id');
    }
}
