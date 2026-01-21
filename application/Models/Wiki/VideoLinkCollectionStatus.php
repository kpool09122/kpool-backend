<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $resource_type
 * @property string $resource_identifier
 * @property Carbon|null $last_collected_at
 * @property Carbon $created_at
 */
class VideoLinkCollectionStatus extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'video_link_collection_statuses';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'resource_type',
        'resource_identifier',
        'last_collected_at',
        'created_at',
    ];

    protected $casts = [
        'last_collected_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
