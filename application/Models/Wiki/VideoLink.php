<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $resource_type
 * @property string $wiki_id
 * @property string $url
 * @property string $video_usage
 * @property string $title
 * @property string|null $thumbnail_url
 * @property Carbon|null $published_at
 * @property int $display_order
 * @property Carbon $created_at
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'resource_type',
    'wiki_id',
    'url',
    'video_usage',
    'title',
    'thumbnail_url',
    'published_at',
    'display_order',
    'created_at',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'video_links', keyType: 'string')]
class VideoLink extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    public $timestamps = false;

    #[\Override]
    protected $casts = [
        'display_order' => 'integer',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
