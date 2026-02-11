<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property ?string $published_id
 * @property string $resource_type
 * @property string $wiki_id
 * @property string $uploader_id
 * @property string $image_path
 * @property string $image_usage
 * @property int $display_order
 * @property string $source_url
 * @property string $source_name
 * @property string $alt_text
 * @property string $status
 * @property Carbon $agreed_to_terms_at
 * @property Carbon $uploaded_at
 */
class DraftWikiImage extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'draft_wiki_images';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'published_id',
        'resource_type',
        'wiki_id',
        'uploader_id',
        'image_path',
        'image_usage',
        'display_order',
        'source_url',
        'source_name',
        'alt_text',
        'status',
        'agreed_to_terms_at',
        'uploaded_at',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'agreed_to_terms_at' => 'datetime',
        'uploaded_at' => 'datetime',
    ];
}
