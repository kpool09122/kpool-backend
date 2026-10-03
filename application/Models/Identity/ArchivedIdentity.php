<?php

declare(strict_types=1);

namespace Application\Models\Identity;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property string $id
 * @property string $identity_id
 * @property string $language
 * @property CarbonImmutable|null $identity_created_at
 * @property CarbonImmutable $archived_at
 */
#[Fillable([
    'id',
    'identity_id',
    'language',
    'identity_created_at',
    'archived_at',
])]
#[Table(name: 'archived_identities', keyType: 'string')]
class ArchivedIdentity extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    #[Override]
    protected function casts(): array
    {
        return [
            'identity_created_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
        ];
    }
}
