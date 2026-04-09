<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $resource_type
 * @property string $wiki_id
 * @property string $image_path
 * @property string $image_usage
 * @property int $display_order
 * @property string $source_url
 * @property string $source_name
 * @property string $alt_text
 * @property bool $is_hidden
 * @property ?string $hidden_by
 * @property ?Carbon $hidden_at
 * @property string $uploader_id
 * @property Carbon $uploaded_at
 * @property ?string $approver_id
 * @property ?Carbon $approved_at
 * @property ?string $updater_id
 * @property ?Carbon $updated_at
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'resource_type',
    'wiki_id',
    'image_path',
    'image_usage',
    'display_order',
    'source_url',
    'source_name',
    'alt_text',
    'is_hidden',
    'hidden_by',
    'hidden_at',
    'uploader_id',
    'uploaded_at',
    'approver_id',
    'approved_at',
    'updater_id',
    'updated_at',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'wiki_images', keyType: 'string')]
class WikiImage extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    public $timestamps = false;

    #[\Override]
    protected $casts = [
        'display_order' => 'integer',
        'is_hidden' => 'boolean',
        'hidden_at' => 'datetime',
        'uploaded_at' => 'datetime',
        'approved_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
