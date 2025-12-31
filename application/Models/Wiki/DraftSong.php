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
 * @property ?string $agency_id
 * @property array $belong_identifiers
 * @property string $lyricist
 * @property string $composer
 * @property \Illuminate\Support\Carbon|null $release_date
 * @property string $overview
 * @property ?string $cover_image_path
 * @property ?string $music_video_link
 * @property string $status
 */
class DraftSong extends Model
{
    public $incrementing = false;

    protected $table = 'draft_songs';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'published_id',
        'translation_set_identifier',
        'editor_id',
        'language',
        'name',
        'agency_id',
        'belong_identifiers',
        'lyricist',
        'composer',
        'release_date',
        'overview',
        'cover_image_path',
        'music_video_link',
        'status',
    ];

    protected $casts = [
        'belong_identifiers' => 'array',
        'release_date' => 'date',
    ];
}
