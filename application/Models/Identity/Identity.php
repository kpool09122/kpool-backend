<?php

declare(strict_types=1);

namespace Application\Models\Identity;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $identity_name
 * @property string $email
 * @property string $language
 * @property ?string $profile_image
 * @property ?Carbon $email_verified_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Collection<int, IdentitySocialConnection> $socialConnections
 * @property-read ?PasskeyUser $passkeyUser
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'identity_name',
    'email',
    'language',
    'profile_image',
    'email_verified_at',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'identities', keyType: 'string')]
class Identity extends Authenticatable
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<IdentitySocialConnection, $this>
     */
    public function socialConnections(): HasMany
    {
        return $this->hasMany(IdentitySocialConnection::class, 'identity_id', 'id');
    }

    /**
     * @return HasOne<PasskeyUser, $this>
     */
    public function passkeyUser(): HasOne
    {
        return $this->hasOne(PasskeyUser::class, 'identity_id', 'id');
    }
}
