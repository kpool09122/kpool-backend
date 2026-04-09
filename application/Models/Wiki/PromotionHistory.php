<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $principal_id
 * @property string $from_role
 * @property string $to_role
 * @property ?string $reason
 * @property Carbon $processed_at
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'principal_id',
    'from_role',
    'to_role',
    'reason',
    'processed_at',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'promotion_histories', keyType: 'string')]
class PromotionHistory extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    public $timestamps = false;

    #[\Override]
    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }
}
