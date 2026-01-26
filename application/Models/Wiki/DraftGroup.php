<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property ?string $published_id
 * @property string $translation_set_identifier
 * @property string $slug
 * @property string $editor_id
 * @property string $translation
 * @property string $name
 * @property string $normalized_name
 * @property ?string $agency_id
 * @property string $description
 * @property ?string $image_path
 * @property string $status
 * @property ?string $approver_id
 * @property ?string $merger_id
 * @property ?string $source_editor_id
 * @property ?Carbon $translated_at
 * @property ?Carbon $approved_at
 */
class DraftGroup extends Model
{
    public $incrementing = false;

    protected $table = 'draft_groups';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'published_id',
        'translation_set_identifier',
        'slug',
        'editor_id',
        'translation',
        'name',
        'normalized_name',
        'agency_id',
        'description',
        'image_path',
        'status',
        'approver_id',
        'merger_id',
        'source_editor_id',
        'translated_at',
        'approved_at',
    ];

    protected $casts = [
        'translated_at' => 'datetime',
        'approved_at' => 'datetime',
    ];
}
