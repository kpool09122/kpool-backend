<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read Account|null $account
 * @property-read AccountSnapshot|null $reviewedSnapshot
 * @property-read AccountSnapshot|null $snapshot
 * @property string $snapshot_id
 * @property ?string $reviewed_snapshot_id
 * @property-read ?string $current_snapshot_id
 * @property string $id
 * @property string $account_id
 * @property string $change_reason
 * @property string $status
 * @property Carbon $requested_at
 * @property ?string $reviewed_by
 * @property ?Carbon $reviewed_at
 * @property array{code: string, detail: string|null}|null $rejection_reason
 */
#[Fillable(['id', 'account_id', 'change_reason', 'status', 'requested_at', 'reviewed_by', 'reviewed_at', 'rejection_reason', 'snapshot_id', 'reviewed_snapshot_id'])]
#[Table(name: 'account_snapshot_change_requests', keyType: 'string')]
class AccountSnapshotChangeRequest extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $incrementing = false;

    public $timestamps = false;

    /** @return BelongsTo<AccountSnapshot, $this> */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(AccountSnapshot::class, 'snapshot_id');
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /** @return BelongsTo<AccountSnapshot, $this> */
    public function reviewedSnapshot(): BelongsTo
    {
        return $this->belongsTo(AccountSnapshot::class, 'reviewed_snapshot_id');
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function withCurrentSnapshotIdentifier(Builder $query): void
    {
        $query->addSelect([
            'current_snapshot_id' => AccountSnapshot::query()
                ->select('id')
                ->whereColumn('account_id', 'account_snapshot_change_requests.account_id')
                ->where('status', 'approved')
                ->orderByDesc('saved_at')
                ->orderByDesc('id')
                ->limit(1),
        ]);
    }

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'rejection_reason' => 'array',
        ];
    }
}
