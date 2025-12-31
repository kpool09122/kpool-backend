<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $group_id
 * @property string $translation_set_identifier
 * @property string $translation
 * @property string $name
 * @property string $normalized_name
 * @property ?string $agency_id
 * @property string $description
 * @property ?string $image_path
 * @property int $version
 * @property Carbon $created_at
 */
class GroupSnapshot extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'group_snapshots';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'group_id',
        'translation_set_identifier',
        'translation',
        'name',
        'normalized_name',
        'agency_id',
        'description',
        'image_path',
        'version',
        'created_at',
    ];

    protected $casts = [
        'version' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsToMany<Song, $this>
     */
    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(
            Song::class,
            'group_snapshot_song',
            'group_snapshot_id',
            'song_id',
        );
    }
}
