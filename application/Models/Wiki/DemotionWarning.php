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
class DemotionWarning extends Model
{
    public $incrementing = false;

    protected $table = 'demotion_warnings';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'principal_id',
        'warning_count',
        'last_warning_month',
    ];

    protected function casts(): array
    {
        return [
            'warning_count' => 'integer',
        ];
    }
}
