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
 * @property string $affiliation_id
 * @property string $delegate_account_id
 * @property string $delegator_account_id
 * @property string $requested_by_account_id
 * @property string $status
 * @property string $direction
 * @property Carbon $requested_at
 * @property ?Carbon $approved_at
 * @property ?Carbon $rejected_at
 * @property-read Account|null $delegateAccount
 * @property-read Account|null $delegatorAccount
 * @property-read Account|null $requestedByAccount
 */
#[Fillable([
    'id', 'affiliation_id', 'delegate_account_id', 'delegator_account_id',
    'requested_by_account_id', 'status', 'direction', 'requested_at', 'approved_at', 'rejected_at',
])]
#[Table(name: 'account_delegations', keyType: 'string')]
class Delegation extends Model
{
    public $incrementing = false;
    public $timestamps = false;

    #[Override]
    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'approved_at' => 'datetime', 'rejected_at' => 'datetime'];
    }

    public function delegateAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'delegate_account_id', 'id');
    }

    public function delegatorAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'delegator_account_id', 'id');
    }

    public function requestedByAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'requested_by_account_id', 'id');
    }
}
