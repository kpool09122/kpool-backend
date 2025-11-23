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
 * @property ?string $agency_id
 * @property string $description
 * @property array $song_identifiers
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
        'agency_id',
        'description',
        'song_identifiers',
        'image_path',
        'status',
    ];

    protected $casts = [
        'song_identifiers' => 'array',
    ];
}
