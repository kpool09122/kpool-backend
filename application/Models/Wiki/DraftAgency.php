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
 * @property string $language
 * @property string $name
 * @property string $normalized_name
 * @property string $CEO
 * @property string $normalized_CEO
 * @property ?Carbon $founded_in
 * @property string $description
 * @property string $status
 * @property ?string $approver_id
 * @property ?string $merger_id
 * @property ?string $source_editor_id
 * @property ?Carbon $translated_at
 * @property ?Carbon $approved_at
 */
class DraftAgency extends Model
{
    public $incrementing = false;

    protected $table = 'draft_agencies';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'published_id',
        'translation_set_identifier',
        'slug',
        'editor_id',
        'language',
        'name',
        'normalized_name',
        'CEO',
        'normalized_CEO',
        'founded_in',
        'description',
        'status',
        'approver_id',
        'merger_id',
        'source_editor_id',
        'translated_at',
        'approved_at',
    ];

    protected $casts = [
        'founded_in' => 'date',
        'translated_at' => 'datetime',
        'approved_at' => 'datetime',
    ];
}
