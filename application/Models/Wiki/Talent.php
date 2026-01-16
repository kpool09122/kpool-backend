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
 * @property string $real_name
 * @property string $normalized_real_name
 * @property ?string $agency_id
 * @property Carbon|null $birthday
 * @property string $career
 * @property ?string $image_link
 * @property array $relevant_video_links
 * @property int $version
 * @property bool $is_official
 * @property ?string $owner_account_id
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
        'normalized_name',
        'real_name',
        'normalized_real_name',
        'agency_id',
        'birthday',
        'career',
        'image_link',
        'relevant_video_links',
        'version',
        'is_official',
        'owner_account_id',
    ];

    protected $casts = [
        'birthday' => 'date',
        'relevant_video_links' => 'array',
        'version' => 'integer',
        'is_official' => 'boolean',
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
