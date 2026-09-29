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
 * @property string $resource_type
 * @property string $wiki_id
 * @property string $contributor_type
 * @property bool $is_new_creation
 * @property ?Carbon $created_at
 */
#[Fillable([
    'id',
    'principal_id',
    'year_month',
    'points',
    'resource_type',
    'wiki_id',
    'contributor_type',
    'is_new_creation',
    'created_at',
])]
#[Table(name: 'contribution_point_histories', keyType: 'string')]
class ContributionPointHistory extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    public $timestamps = false;

    #[Override]
    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'is_new_creation' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
