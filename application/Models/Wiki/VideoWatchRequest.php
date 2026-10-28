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
 * @property string $wiki_id
 * @property string $principal_id
 * @property-read Wiki|null $wiki
 * @property-read VideoLink|null $videoLink
 * @property Carbon $sent_at
 */
#[Fillable(['id', 'video_link_id', 'wiki_id', 'principal_id', 'sent_at'])]
#[Table(name: 'wiki_video_watch_requests', keyType: 'string')]
class VideoWatchRequest extends Model
{
    #[Override]
    public $incrementing = false;
    #[Override]
    public $timestamps = false;
    #[Override]
    protected $casts = ['sent_at' => 'datetime'];

    /** @return BelongsTo<Wiki, $this> */
    public function wiki(): BelongsTo
    {
        return $this->belongsTo(Wiki::class, 'wiki_id', 'id');
    }

    /** @return BelongsTo<VideoLink, $this> */
    public function videoLink(): BelongsTo
    {
        return $this->belongsTo(VideoLink::class, 'video_link_id', 'id');
    }
}
