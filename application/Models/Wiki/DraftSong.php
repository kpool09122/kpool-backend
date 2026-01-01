<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property ?string $published_id
 * @property string $translation_set_identifier
 * @property string $editor_id
 * @property string $language
 * @property string $name
 * @property ?string $agency_id
 * @property string $lyricist
 * @property string $composer
 * @property Carbon|null $release_date
 * @property string $overview
 * @property ?string $cover_image_path
 * @property ?string $music_video_link
 * @property string $status
 * @property-read Collection<int, Group> $groups
 * @property-read Collection<int, Talent> $talents
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
        'lyricist',
        'composer',
        'release_date',
        'overview',
        'cover_image_path',
        'music_video_link',
        'status',
    ];

    protected $casts = [
        'release_date' => 'date',
    ];

    /**
     * @return BelongsToMany<Group, $this>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            Group::class,
            'draft_song_group',
            'draft_song_id',
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
            'draft_song_talent',
            'draft_song_id',
            'talent_id',
        );
    }
}
