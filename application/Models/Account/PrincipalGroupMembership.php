<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string $id
 * @property string $principal_group_id
 * @property string $principal_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Principal|null $principal
 */
#[Fillable([
    'id',
    'principal_group_id',
    'principal_id',
])]
#[Table(name: 'account_principal_group_memberships', keyType: 'string')]
class PrincipalGroupMembership extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
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
