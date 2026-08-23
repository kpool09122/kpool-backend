<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Application\Models\Account\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $resource_type
 * @property string $translation_set_identifier
 * @property string $owner_account_id
 * @property string $status
 * @property Carbon $requested_at
 * @property ?Carbon $approved_at
 * @property ?Carbon $rejected_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Account|null $ownerAccount
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Wiki> $wikis
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'resource_type',
    'translation_set_identifier',
    'owner_account_id',
    'status',
    'requested_at',
    'approved_at',
    'rejected_at',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'official_certifications', keyType: 'string')]
class OfficialCertification extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function ownerAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'owner_account_id', 'id');
    }

    /**
     * @return HasMany<Wiki, $this>
     */
    public function wikis(): HasMany
    {
        return $this->hasMany(Wiki::class, 'translation_set_identifier', 'translation_set_identifier');
    }
}
