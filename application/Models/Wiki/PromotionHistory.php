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
class PromotionHistory extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'promotion_histories';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'principal_id',
        'from_role',
        'to_role',
        'reason',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }
}
