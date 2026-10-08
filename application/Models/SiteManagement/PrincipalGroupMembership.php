<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string $principal_group_id
 * @property string $principal_id
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read PrincipalGroup|null $principalGroup
 * @property-read Principal|null $principal
 */
#[Fillable([
    'principal_group_id',
    'principal_id',
])]
#[Table(name: 'site_management_principal_group_memberships', keyType: 'string')]
class PrincipalGroupMembership extends Model
{
    #[Override]
    public $incrementing = false;

    /** @var string[] */
    protected $primaryKey = ['principal_group_id', 'principal_id'];

    #[Override]
    public function getKey(): string
    {
        return $this->principal_group_id . '_' . $this->principal_id;
    }

    #[Override]
    protected function setKeysForSelectQuery($query): mixed
    {
        return $query->where('principal_group_id', $this->getRawOriginal('principal_group_id', $this->principal_group_id))
            ->where('principal_id', $this->getRawOriginal('principal_id', $this->principal_id));
    }

    #[Override]
    protected function setKeysForSaveQuery($query): mixed
    {
        return $this->setKeysForSelectQuery($query);
    }

    /** @return BelongsTo<PrincipalGroup, $this> */
    public function principalGroup(): BelongsTo
    {
        return $this->belongsTo(PrincipalGroup::class, 'principal_group_id', 'id');
    }

    /** @return BelongsTo<Principal, $this> */
    public function principal(): BelongsTo
    {
        return $this->belongsTo(Principal::class, 'principal_id', 'id');
    }
}
