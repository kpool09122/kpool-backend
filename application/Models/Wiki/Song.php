<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $translation_set_identifier
 * @property string $language
 * @property string $name
 * @property string $normalized_name
 * @property ?string $agency_id
 * @property array $belong_identifiers
 * @property string $lyricist
 * @property string $normalized_lyricist
 * @property string $composer
 * @property string $normalized_composer
 * @property Carbon|null $release_date
 * @property string $overview
 * @property ?string $cover_image_path
 * @property ?string $music_video_link
 * @property int $version
 * @property bool $is_official
 * @property ?string $owner_account_id
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Group> $groups
 * @property-read Collection<int, Talent> $talents
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
        'normalized_name',
        'agency_id',
        'lyricist',
        'normalized_lyricist',
        'composer',
        'normalized_composer',
        'release_date',
        'overview',
        'cover_image_path',
        'music_video_link',
        'version',
        'is_official',
        'owner_account_id',
    ];

    protected $casts = [
        'release_date' => 'date',
        'version' => 'integer',
        'deleted_at' => 'datetime',
        'is_official' => 'boolean',
    ];

    /**
     * @return BelongsToMany<Group, $this>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            Group::class,
            'song_group',
            'song_id',
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
            'song_talent',
            'song_id',
            'talent_id',
        );
    }
}
