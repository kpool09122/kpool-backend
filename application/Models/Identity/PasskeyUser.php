<?php

declare(strict_types=1);

namespace Application\Models\Identity;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property ?string $identity_id
 * @property ?Carbon $created_at
 * @property-read ?Identity $identity
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PasskeyCredential> $credentials
 */
#[Fillable(['id', 'identity_id'])]
#[Table(name: 'passkey_users', keyType: 'string')]
class PasskeyUser extends Model
{
    public const UPDATED_AT = null;

    #[\Override]
    public $incrementing = false;

    /** @return BelongsTo<Identity, $this> */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_id', 'id');
    }

    /** @return HasMany<PasskeyCredential, $this> */
    public function credentials(): HasMany
    {
        return $this->hasMany(PasskeyCredential::class, 'passkey_user_id', 'id');
    }
}
