<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $account_id
 * @property string $identity_id
 * @property string $role
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class AccountMembership extends Model
{
    public $incrementing = false;

    protected $table = 'account_memberships';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'account_id',
        'identity_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }
}
