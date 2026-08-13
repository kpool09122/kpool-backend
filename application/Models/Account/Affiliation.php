<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $agency_account_id
 * @property string $talent_account_id
 * @property string $requested_by
 * @property string $status
 * @property ?int $revenue_share_percentage
 * @property ?string $contract_notes
 * @property \Illuminate\Support\Carbon $requested_at
 * @property ?\Illuminate\Support\Carbon $activated_at
 * @property ?\Illuminate\Support\Carbon $terminated_at
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'agency_account_id',
    'talent_account_id',
    'requested_by',
    'status',
    'revenue_share_percentage',
    'contract_notes',
    'requested_at',
    'activated_at',
    'terminated_at',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'account_affiliations', keyType: 'string')]
class Affiliation extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    #[\Override]
    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'activated_at' => 'datetime',
            'terminated_at' => 'datetime',
        ];
    }
}
