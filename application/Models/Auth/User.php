<?php

declare(strict_types=1);

namespace Application\Models\Auth;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $username
 * @property string $email
 * @property string $language
 * @property ?string $profile_image
 * @property string $password
 * @property ?Carbon $email_verified_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class User extends Authenticatable
{
    public $incrementing = false;

    protected $table = 'users';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'username',
        'email',
        'language',
        'profile_image',
        'password',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
