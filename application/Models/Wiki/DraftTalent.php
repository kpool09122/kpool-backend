<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property ?string $published_id
 * @property string $translation_set_identifier
 * @property string $editor_id
 * @property string $language
 * @property string $name
 * @property string $real_name
 * @property ?string $agency_id
 * @property array $group_identifiers
 * @property \Illuminate\Support\Carbon|null $birthday
 * @property string $career
 * @property ?string $image_link
 * @property array $relevant_video_links
 * @property string $status
 */
class DraftTalent extends Model
{
    public $incrementing = false;

    protected $table = 'draft_talents';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'published_id',
        'translation_set_identifier',
        'editor_id',
        'language',
        'name',
        'real_name',
        'agency_id',
        'group_identifiers',
        'birthday',
        'career',
        'image_link',
        'relevant_video_links',
        'status',
    ];

    protected $casts = [
        'group_identifiers' => 'array',
        'birthday' => 'date',
        'relevant_video_links' => 'array',
    ];
}
