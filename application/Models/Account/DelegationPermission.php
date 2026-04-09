<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $identity_group_id
 * @property string $target_account_id
 * @property string $affiliation_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class DelegationPermission extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected $table = 'delegation_permissions';

    #[\Override]
    protected $keyType = 'string';

    #[\Override]
    protected $fillable = [
        'id',
        'identity_group_id',
        'target_account_id',
        'affiliation_id',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
