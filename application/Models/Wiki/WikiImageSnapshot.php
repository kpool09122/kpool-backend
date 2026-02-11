<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $image_id
 * @property string $wiki_id
 * @property string $image_path
 * @property string $image_usage
 * @property int $display_order
 * @property string $source_url
 * @property string $source_name
 * @property string $alt_text
 * @property string $uploader_id
 * @property Carbon $uploaded_at
 * @property ?string $approver_id
 * @property ?Carbon $approved_at
 * @property ?string $updater_id
 * @property ?Carbon $updated_at
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
        'wiki_id',
        'image_path',
        'image_usage',
        'display_order',
        'source_url',
        'source_name',
        'alt_text',
        'uploader_id',
        'uploaded_at',
        'approver_id',
        'approved_at',
        'updater_id',
        'updated_at',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'uploaded_at' => 'datetime',
        'approved_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
