<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $talent_id
 * @property string $translation_set_identifier
 * @property string $language
 * @property string $name
 * @property string $real_name
 * @property ?string $agency_id
 * @property ?Carbon $birthday
 * @property string $career
 * @property ?string $image_link
 * @property array<int, string>|null $relevant_video_links
 * @property int $version
 * @property Carbon $created_at
 */
class TalentSnapshot extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'talent_snapshots';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'talent_id',
        'translation_set_identifier',
        'language',
        'name',
        'real_name',
        'agency_id',
        'birthday',
        'career',
        'image_link',
        'relevant_video_links',
        'version',
        'created_at',
    ];

    protected $casts = [
        'relevant_video_links' => 'array',
        'birthday' => 'date',
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
            'talent_snapshot_group',
            'talent_snapshot_id',
            'group_id',
        );
    }
}
