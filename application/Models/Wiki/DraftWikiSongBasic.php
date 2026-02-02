<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $wiki_id
 * @property string $name
 * @property string $normalized_name
 * @property ?string $song_type
 * @property array<string> $genres
 * @property ?string $agency_identifier
 * @property array<string> $group_identifiers
 * @property array<string> $talent_identifiers
 * @property ?string $release_date
 * @property ?string $album_name
 * @property ?string $cover_image_identifier
 * @property string $lyricist
 * @property string $normalized_lyricist
 * @property string $composer
 * @property string $normalized_composer
 * @property string $arranger
 * @property string $normalized_arranger
 */
class DraftWikiSongBasic extends Model
{
    public $incrementing = false;

    protected $table = 'draft_wiki_song_basics';

    protected $primaryKey = 'wiki_id';

    protected $keyType = 'string';

    protected $fillable = [
        'wiki_id',
        'name',
        'normalized_name',
        'song_type',
        'genres',
        'agency_identifier',
        'group_identifiers',
        'talent_identifiers',
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
        'group_identifiers' => 'array',
        'talent_identifiers' => 'array',
    ];

    /**
     * @return BelongsTo<DraftWiki, DraftWikiSongBasic>
     */
    public function draftWiki(): BelongsTo
    {
        return $this->belongsTo(DraftWiki::class, 'wiki_id', 'id');
    }
}
