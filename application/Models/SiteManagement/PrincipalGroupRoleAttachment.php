<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property string $principal_group_id
 * @property string $role_id
 * @property-read Role|null $role
 */
#[Fillable([
    'principal_group_id',
    'role_id',
])]
#[Table(name: 'site_management_principal_group_role_attachments', keyType: 'string')]
class PrincipalGroupRoleAttachment extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    public $timestamps = false;

    /** @var string[] */
    protected $primaryKey = ['principal_group_id', 'role_id'];

    #[Override]
    public function getKey(): string
    {
        return $this->principal_group_id . '_' . $this->role_id;
    }

    #[Override]
    protected function setKeysForSelectQuery($query): mixed
    {
        return $query->where('principal_group_id', $this->getRawOriginal('principal_group_id', $this->principal_group_id))
            ->where('role_id', $this->getRawOriginal('role_id', $this->role_id));
    }

    #[Override]
    protected function setKeysForSaveQuery($query): mixed
    {
        return $this->setKeysForSelectQuery($query);
    }

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }
}
