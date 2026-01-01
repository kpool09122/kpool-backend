<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property ?string $published_id
 * @property string $translation_set_identifier
 * @property string $editor_id
 * @property string $translation
 * @property string $name
 * @property string $normalized_name
 * @property ?string $agency_id
 * @property string $description
 * @property ?string $image_path
 * @property string $status
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
        'editor_id',
        'translation',
        'name',
        'normalized_name',
        'agency_id',
        'description',
        'image_path',
        'status',
    ];
}
