<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $principal_id
 * @property int $warning_count
 * @property string $last_warning_month
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'principal_id',
    'warning_count',
    'last_warning_month',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'demotion_warnings', keyType: 'string')]
class DemotionWarning extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected function casts(): array
    {
        return [
            'warning_count' => 'integer',
        ];
    }
}
