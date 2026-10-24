<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property string $id
 * @property string $resource_type
 * @property string $wiki_id
 * @property string $url
 * @property string $video_usage
 * @property string $title
 * @property string|null $thumbnail_url
 * @property Carbon|null $published_at
 * @property int|null $duration_seconds
 * @property string|null $creator_principal_id
 * @property Carbon $created_at
 * @property-read int $likes_count
 * @property-read Principal|null $creatorPrincipal
 */
#[Fillable([
    'id',
    'resource_type',
    'wiki_id',
    'url',
    'video_usage',
    'title',
    'thumbnail_url',
    'published_at',
    'duration_seconds',
    'creator_principal_id',
    'created_at',
])]
#[Table(name: 'wiki_video_links', keyType: 'string')]
class VideoLink extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    public $timestamps = false;

    #[Override]
    protected $casts = [
        'duration_seconds' => 'integer',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /** @return HasMany<VideoLinkLike, $this> */
    public function likes(): HasMany
    {
        return $this->hasMany(VideoLinkLike::class, 'video_link_id', 'id');
    }

    /** @return BelongsTo<Principal, $this> */
    public function creatorPrincipal(): BelongsTo
    {
        return $this->belongsTo(Principal::class, 'creator_principal_id', 'id');
    }
}
