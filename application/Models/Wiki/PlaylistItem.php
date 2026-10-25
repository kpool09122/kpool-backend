<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property string $id
 * @property string $playlist_id
 * @property ?string $video_link_id
 * @property string $provider
 * @property string $external_video_id
 * @property string $url
 * @property string $title
 * @property ?string $thumbnail_url
 * @property ?int $duration_seconds
 * @property int $position
 */
#[Fillable(['id', 'playlist_id', 'video_link_id', 'provider', 'external_video_id', 'url', 'title', 'thumbnail_url', 'duration_seconds', 'position'])]
#[Table(name: 'wiki_playlist_items', keyType: 'string')]
class PlaylistItem extends Model
{
    #[Override]
    public $incrementing = false;
    #[Override]
    public $timestamps = false;
    #[Override]
    protected $casts = ['duration_seconds' => 'integer', 'position' => 'integer'];
}
