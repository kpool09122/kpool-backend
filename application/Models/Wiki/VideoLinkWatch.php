<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property string $id
 * @property string $video_link_id
 * @property string $owner_account_id
 * @property string $principal_id
 * @property Carbon $watched_at
 */
#[Fillable(['id', 'video_link_id', 'owner_account_id', 'principal_id', 'watched_at'])]
#[Table(name: 'wiki_video_link_watches', keyType: 'string')]
class VideoLinkWatch extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    public $timestamps = false;

    #[Override]
    protected $casts = ['watched_at' => 'datetime'];
}
