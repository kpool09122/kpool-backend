<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $resource_type
 * @property string $resource_identifier
 * @property string $url
 * @property string $video_usage
 * @property string $title
 * @property int $display_order
 * @property Carbon $created_at
 */
class VideoLink extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'video_links';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'resource_type',
        'resource_identifier',
        'url',
        'video_usage',
        'title',
        'display_order',
        'created_at',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'created_at' => 'datetime',
    ];
}
