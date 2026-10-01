<?php

declare(strict_types=1);

namespace Application\Models\Shared;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property string $id
 * @property string $identity_id
 * @property string $principal_type
 * @property string $principal_id
 * @property string $account_id
 * @property CarbonImmutable $archived_at
 */
#[Fillable([
    'id',
    'identity_id',
    'principal_type',
    'principal_id',
    'account_id',
    'archived_at',
])]
#[Table(name: 'archived_principals', keyType: 'string')]
class ArchivedPrincipal extends Model
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
