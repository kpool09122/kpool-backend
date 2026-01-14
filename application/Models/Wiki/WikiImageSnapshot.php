<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $image_id
 * @property string $resource_snapshot_identifier
 * @property string $image_path
 * @property string $image_usage
 * @property int $display_order
 * @property Carbon $created_at
 */
class WikiImageSnapshot extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'wiki_image_snapshots';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'image_id',
        'resource_snapshot_identifier',
        'image_path',
        'image_usage',
        'display_order',
        'created_at',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'created_at' => 'datetime',
    ];
}
