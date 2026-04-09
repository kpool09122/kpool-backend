<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $resource_type
 * @property string $wiki_id
 * @property Carbon|null $last_collected_at
 * @property Carbon $created_at
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'resource_type',
    'wiki_id',
    'last_collected_at',
    'created_at',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'video_link_collection_statuses', keyType: 'string')]
class VideoLinkCollectionStatus extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    public $timestamps = false;

    #[\Override]
    protected $casts = [
        'last_collected_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
