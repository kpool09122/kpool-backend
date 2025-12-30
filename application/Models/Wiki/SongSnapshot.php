<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $song_id
 * @property string $translation_set_identifier
 * @property string $language
 * @property string $name
 * @property ?string $agency_id
 * @property array<int, string>|null $belong_identifiers
 * @property string $lyricist
 * @property string $composer
 * @property ?Carbon $release_date
 * @property string $overview
 * @property ?string $cover_image_path
 * @property ?string $music_video_link
 * @property int $version
 * @property Carbon $created_at
 */
class SongSnapshot extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'song_snapshots';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'song_id',
        'translation_set_identifier',
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
        'version',
        'created_at',
    ];

    protected $casts = [
        'belong_identifiers' => 'array',
        'release_date' => 'date',
        'version' => 'integer',
        'created_at' => 'datetime',
    ];
}
