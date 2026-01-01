<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Ramsey\Collection\Collection;

/**
 * @property string $id
 * @property string $song_id
 * @property string $translation_set_identifier
 * @property string $language
 * @property string $name
 * @property ?string $agency_id
 * @property string $lyricist
 * @property string $composer
 * @property ?Carbon $release_date
 * @property string $overview
 * @property ?string $cover_image_path
 * @property ?string $music_video_link
 * @property int $version
 * @property Carbon $created_at
 * @property-read Collection<int, Group> $groups
 * @property-read Collection<int, Talent> $talents
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
        'release_date' => 'date',
        'version' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsToMany<Group, $this>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            Group::class,
            'song_snapshot_group',
            'song_snapshot_id',
            'group_id',
        );
    }

    /**
     * @return BelongsToMany<Talent, $this>
     */
    public function talents(): BelongsToMany
    {
        return $this->belongsToMany(
            Talent::class,
            'song_snapshot_talent',
            'song_snapshot_id',
            'talent_id',
        );
    }
}
