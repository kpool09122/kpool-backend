<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/** @property string $path */
#[Fillable(['path'])]
#[Table(name: 'wiki_video_thumbnails', keyType: 'string')]
class VideoThumbnail extends Model
{
    #[Override]
    public $incrementing = false;
    #[Override]
    public $timestamps = false;
    #[Override]
    protected $primaryKey = 'path';

    /** @return HasMany<VideoLink, $this> */
    public function videoLinks(): HasMany
    {
        return $this->hasMany(VideoLink::class, 'thumbnail_path', 'path');
    }

    /** @return HasMany<PlaylistItem, $this> */
    public function playlistItems(): HasMany
    {
        return $this->hasMany(PlaylistItem::class, 'thumbnail_path', 'path');
    }
}
