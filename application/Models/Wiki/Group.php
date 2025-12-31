<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property string $translation_set_identifier
 * @property string $translation
 * @property string $name
 * @property string $normalized_name
 * @property ?string $agency_id
 * @property string $description
 * @property ?string $image_path
 * @property int|null $version
 */
class Group extends Model
{
    public $incrementing = false;

    protected $table = 'groups';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'translation_set_identifier',
        'translation',
        'name',
        'normalized_name',
        'agency_id',
        'description',
        'image_path',
        'version',
    ];

    protected $casts = [
        'version' => 'integer',
    ];

    /**
     * @return BelongsToMany<Song, $this>
     */
    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(
            Song::class,
            'group_song',
            'group_id',
            'song_id',
        );
    }
}
