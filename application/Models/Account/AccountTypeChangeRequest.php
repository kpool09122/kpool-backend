<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $account_id
 * @property string $current_account_type
 * @property string $requested_account_type
 * @property string $status
 * @property Carbon $requested_at
 * @property ?string $reviewed_by
 * @property ?Carbon $reviewed_at
 * @property ?array $rejection_reason
 */
#[Fillable(['id', 'account_id', 'current_account_type', 'requested_account_type', 'status', 'requested_at', 'reviewed_by', 'reviewed_at', 'rejection_reason'])]
#[Table(name: 'account_type_change_requests', keyType: 'string')]
class AccountTypeChangeRequest extends Model
{
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'rejection_reason' => 'array',
        ];
    }
}
