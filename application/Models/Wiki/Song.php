<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $translation_set_identifier
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
 * @property int|null $version
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Song extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $table = 'songs';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
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
    ];

    protected $casts = [
        'belong_identifiers' => 'array',
        'release_date' => 'date',
        'version' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * @return BelongsToMany<Group, $this>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            Group::class,
            'group_song',
            'song_id',
            'group_id',
        );
    }
}
