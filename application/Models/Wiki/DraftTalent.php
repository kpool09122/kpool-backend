<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property ?string $published_id
 * @property string $translation_set_identifier
 * @property string $editor_id
 * @property string $language
 * @property string $name
 * @property string $real_name
 * @property ?string $agency_id
 * @property \Illuminate\Support\Carbon|null $birthday
 * @property string $career
 * @property ?string $image_link
 * @property array $relevant_video_links
 * @property string $status
 */
class DraftTalent extends Model
{
    public $incrementing = false;

    protected $table = 'draft_talents';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'published_id',
        'translation_set_identifier',
        'editor_id',
        'language',
        'name',
        'real_name',
        'agency_id',
        'birthday',
        'career',
        'image_link',
        'relevant_video_links',
        'status',
    ];

    protected $casts = [
        'birthday' => 'date',
        'relevant_video_links' => 'array',
    ];

    /**
     * @return BelongsToMany<Group, $this>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            Group::class,
            'draft_talent_group',
            'draft_talent_id',
            'group_id',
        );
    }
}
