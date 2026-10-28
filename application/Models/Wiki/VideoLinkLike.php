<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property string $id
 * @property string $video_link_id
 * @property string $principal_id
 * @property bool $is_official_owner
 * @property Carbon $created_at
 * @property-read VideoLink|null $videoLink
 */
#[Fillable(['id', 'video_link_id', 'principal_id', 'created_at', 'is_official_owner'])]
#[Table(name: 'wiki_video_link_likes', keyType: 'string')]
class VideoLinkLike extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    public $timestamps = false;

    #[Override]
    protected $casts = ['created_at' => 'datetime', 'is_official_owner' => 'boolean'];

    /** @return BelongsTo<VideoLink, $this> */
    public function videoLink(): BelongsTo
    {
        return $this->belongsTo(VideoLink::class, 'video_link_id', 'id');
    }
}
