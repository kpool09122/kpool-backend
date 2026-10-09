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
 * @property string $action_type
 * @property string $actor_id
 * @property ?string $submitter_id
 * @property ?string $wiki_id
 * @property ?string $draft_wiki_id
 * @property ?string $from_status
 * @property ?string $to_status
 * @property ?int $from_version
 * @property ?int $to_version
 * @property ?string $visitor_country
 * @property ?string $visitor_region
 * @property string $subject_name
 * @property ?Carbon $recorded_at
 */
#[Fillable([
    'id',
    'action_type',
    'actor_id',
    'submitter_id',
    'wiki_id',
    'draft_wiki_id',
    'from_status',
    'to_status',
    'from_version',
    'to_version',
    'subject_name',
    'recorded_at',
    'visitor_country',
    'visitor_region',
])]
#[Table(name: 'wiki_histories', keyType: 'string')]
class WikiHistory extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    public $timestamps = false;

    #[Override]
    protected function casts(): array
    {
        return [
            'from_version' => 'integer',
            'to_version' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }
}
