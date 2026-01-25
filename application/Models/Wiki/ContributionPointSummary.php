<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $principal_id
 * @property string $year_month
 * @property int $points
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class ContributionPointSummary extends Model
{
    public $incrementing = false;

    protected $table = 'contribution_point_summaries';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'principal_id',
        'year_month',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
        ];
    }
}
