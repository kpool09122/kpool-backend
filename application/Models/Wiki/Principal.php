<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Application\Models\Identity\Identity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $identity_id
 * @property ?string $delegation_identifier
 * @property bool $enabled
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Collection<int, PrincipalGroupMembership> $memberships
 * @property-read Identity|null $identity
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'identity_id',
    'delegation_identifier',
    'enabled',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'wiki_principals', keyType: 'string')]
class Principal extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    /**
     * @return HasMany<PrincipalGroupMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(PrincipalGroupMembership::class, 'principal_id', 'id');
    }

    /**
     * @return BelongsTo<Identity, $this>
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_id', 'id');
    }
}
