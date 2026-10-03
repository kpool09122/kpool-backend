<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string $id
 * @property string $principal_id
 * @property int $warning_count
 * @property string $last_warning_month
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
#[Fillable([
    'id',
    'principal_id',
    'warning_count',
    'last_warning_month',
])]
#[Table(name: 'demotion_warnings', keyType: 'string')]
class DemotionWarning extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    protected function casts(): array
    {
        return [
            'warning_count' => 'integer',
        ];
    }
}
