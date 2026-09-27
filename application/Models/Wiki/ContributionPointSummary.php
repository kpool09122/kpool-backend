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
 * @property string $year_month
 * @property int $points
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
#[Fillable([
    'id',
    'principal_id',
    'year_month',
    'points',
])]
#[Table(name: 'contribution_point_summaries', keyType: 'string')]
class ContributionPointSummary extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    protected function casts(): array
    {
        return [
            'points' => 'integer',
        ];
    }
}
