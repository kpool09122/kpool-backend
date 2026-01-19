<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $account_id
 * @property string $invited_by_identity_id
 * @property string $email
 * @property string $token
 * @property string $status
 * @property Carbon $expires_at
 * @property string|null $accepted_by_identity_id
 * @property Carbon|null $accepted_at
 * @property Carbon $created_at
 */
class Invitation extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'invitations';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'account_id',
        'invited_by_identity_id',
        'email',
        'token',
        'status',
        'expires_at',
        'accepted_by_identity_id',
        'accepted_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
