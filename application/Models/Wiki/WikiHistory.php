<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
 * @property string $subject_name
 * @property ?Carbon $recorded_at
 */
class WikiHistory extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'wiki_histories';

    protected $keyType = 'string';

    protected $fillable = [
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
    ];

    protected function casts(): array
    {
        return [
            'from_version' => 'integer',
            'to_version' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }
}
