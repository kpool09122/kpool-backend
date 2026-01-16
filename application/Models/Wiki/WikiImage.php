<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $resource_type
 * @property string $resource_identifier
 * @property string $image_path
 * @property string $image_usage
 * @property int $display_order
 * @property string $source_url
 * @property string $source_name
 * @property string $alt_text
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class WikiImage extends Model
{
    public $incrementing = false;

    protected $table = 'wiki_images';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'resource_type',
        'resource_identifier',
        'image_path',
        'image_usage',
        'display_order',
        'source_url',
        'source_name',
        'alt_text',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];
}
