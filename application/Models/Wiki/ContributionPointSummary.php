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
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'principal_id',
    'year_month',
    'points',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'contribution_point_summaries', keyType: 'string')]
class ContributionPointSummary extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected function casts(): array
    {
        return [
            'points' => 'integer',
        ];
    }
}
