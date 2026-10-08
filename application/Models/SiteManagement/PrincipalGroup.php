<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Application\Models\Account\Account;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string $id
 * @property string $account_id
 * @property bool $is_default
 * @property string $name
 * @property-read Account|null $account
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Collection<int, PrincipalGroupMembership> $memberships
 * @property-read Collection<int, PrincipalGroupRoleAttachment> $roleAttachments
 */
#[Fillable([
    'id',
    'account_id',
    'name',
    'is_default',
])]
#[Table(name: 'site_management_principal_groups', keyType: 'string')]
class PrincipalGroup extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    /** @return HasMany<PrincipalGroupMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(PrincipalGroupMembership::class, 'principal_group_id', 'id');
    }

    /** @return HasMany<PrincipalGroupRoleAttachment, $this> */
    public function roleAttachments(): HasMany
    {
        return $this->hasMany(PrincipalGroupRoleAttachment::class, 'principal_group_id', 'id');
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }
}
