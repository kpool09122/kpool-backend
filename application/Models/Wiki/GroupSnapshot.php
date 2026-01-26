<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $group_id
 * @property string $translation_set_identifier
 * @property string $slug
 * @property string $translation
 * @property string $name
 * @property string $normalized_name
 * @property ?string $agency_id
 * @property string $description
 * @property ?string $image_path
 * @property int $version
 * @property Carbon $created_at
 * @property ?string $editor_id
 * @property ?string $approver_id
 * @property ?string $merger_id
 * @property ?Carbon $merged_at
 * @property ?string $source_editor_id
 * @property ?Carbon $translated_at
 * @property ?Carbon $approved_at
 */
class GroupSnapshot extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'group_snapshots';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'group_id',
        'translation_set_identifier',
        'slug',
        'translation',
        'name',
        'normalized_name',
        'agency_id',
        'description',
        'image_path',
        'version',
        'created_at',
        'editor_id',
        'approver_id',
        'merger_id',
        'merged_at',
        'source_editor_id',
        'translated_at',
        'approved_at',
    ];

    protected $casts = [
        'version' => 'integer',
        'created_at' => 'datetime',
        'merged_at' => 'datetime',
        'translated_at' => 'datetime',
        'approved_at' => 'datetime',
    ];
}
