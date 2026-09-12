<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $affiliation_id
 * @property string $delegate_account_id
 * @property string $delegator_account_id
 * @property string $requested_by_account_id
 * @property string $status
 * @property string $direction
 * @property \Illuminate\Support\Carbon $requested_at
 * @property ?\Illuminate\Support\Carbon $approved_at
 * @property ?\Illuminate\Support\Carbon $revoked_at
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id', 'affiliation_id', 'delegate_account_id', 'delegator_account_id',
    'requested_by_account_id', 'status', 'direction', 'requested_at', 'approved_at', 'revoked_at',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'account_delegations', keyType: 'string')]
class AccountDelegation extends Model
{
    public $incrementing = false;
    public $timestamps = false;

    #[\Override]
    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'approved_at' => 'datetime', 'revoked_at' => 'datetime'];
    }
}
