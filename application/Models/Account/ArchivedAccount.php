<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property string $id
 * @property string $account_id
 * @property string $account_category
 * @property string $account_type
 * @property CarbonImmutable $archived_at
 */
#[Fillable([
    'id',
    'account_id',
    'account_category',
    'account_type',
    'archived_at',
])]
#[Table(name: 'archived_accounts', keyType: 'string')]
class ArchivedAccount extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    #[Override]
    protected function casts(): array
    {
        return [
            'archived_at' => 'immutable_datetime',
        ];
    }
}
