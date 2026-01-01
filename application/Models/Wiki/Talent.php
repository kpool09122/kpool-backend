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
 * @property string $real_name
 * @property ?string $agency_id
 * @property Carbon|null $birthday
 * @property string $career
 * @property ?string $image_link
 * @property array $relevant_video_links
 * @property int|null $version
 * @property-read Collection<int, Group> $groups
 */
class Talent extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $table = 'talents';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
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
    ];

    protected $casts = [
        'birthday' => 'date',
        'relevant_video_links' => 'array',
        'version' => 'integer',
    ];

    /**
     * @return BelongsToMany<Group, $this>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            Group::class,
            'talent_group',
            'talent_id',
            'group_id',
        );
    }
}
