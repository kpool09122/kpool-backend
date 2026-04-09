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
 * @property string $resource_type
 * @property string $wiki_id
 * @property string $contributor_type
 * @property bool $is_new_creation
 * @property ?Carbon $created_at
 */
class ContributionPointHistory extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    public $timestamps = false;

    #[\Override]
    protected $table = 'contribution_point_histories';

    #[\Override]
    protected $keyType = 'string';

    #[\Override]
    protected $fillable = [
        'id',
        'principal_id',
        'year_month',
        'points',
        'resource_type',
        'wiki_id',
        'contributor_type',
        'is_new_creation',
        'created_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'is_new_creation' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
