<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $snapshot_id
 * @property string $name
 * @property string $normalized_name
 * @property ?string $song_type
 * @property array<string> $genres
 * @property ?string $agency_identifier
 * @property ?string $release_date
 * @property ?string $album_name
 * @property ?string $cover_image_identifier
 * @property string $lyricist
 * @property string $normalized_lyricist
 * @property string $composer
 * @property string $normalized_composer
 * @property string $arranger
 * @property string $normalized_arranger
 * @property-read Collection<int, Wiki> $groups
 * @property-read Collection<int, Wiki> $talents
 */
class WikiSnapshotSongBasic extends Model
{
    public $incrementing = false;

    protected $table = 'wiki_snapshot_song_basics';

    protected $primaryKey = 'snapshot_id';

    protected $keyType = 'string';

    protected $fillable = [
        'snapshot_id',
        'name',
        'normalized_name',
        'song_type',
        'genres',
        'agency_identifier',
        'release_date',
        'album_name',
        'cover_image_identifier',
        'lyricist',
        'normalized_lyricist',
        'composer',
        'normalized_composer',
        'arranger',
        'normalized_arranger',
    ];

    protected $casts = [
        'genres' => 'array',
    ];

    /**
     * @return BelongsTo<WikiSnapshot, WikiSnapshotSongBasic>
     */
    public function wikiSnapshot(): BelongsTo
    {
        return $this->belongsTo(WikiSnapshot::class, 'snapshot_id', 'id');
    }

    /**
     * @return BelongsToMany<Wiki>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Wiki::class, 'wiki_snapshot_song_basic_groups', 'snapshot_id', 'group_identifier');
    }

    /**
     * @return BelongsToMany<Wiki>
     */
    public function talents(): BelongsToMany
    {
        return $this->belongsToMany(Wiki::class, 'wiki_snapshot_song_basic_talents', 'snapshot_id', 'talent_identifier');
    }
}
