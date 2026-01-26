<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property ?string $published_id
 * @property string $translation_set_identifier
 * @property string $slug
 * @property string $editor_id
 * @property string $language
 * @property string $name
 * @property string $real_name
 * @property ?string $agency_id
 * @property Carbon|null $birthday
 * @property string $career
 * @property ?string $image_link
 * @property array $relevant_video_links
 * @property string $status
 * @property ?string $approver_id
 * @property ?string $merger_id
 * @property ?string $source_editor_id
 * @property ?Carbon $translated_at
 * @property ?Carbon $approved_at
 * @property-read Collection<int, Group> $groups
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
        'slug',
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
        'approver_id',
        'merger_id',
        'source_editor_id',
        'translated_at',
        'approved_at',
    ];

    protected $casts = [
        'birthday' => 'date',
        'relevant_video_links' => 'array',
        'translated_at' => 'datetime',
        'approved_at' => 'datetime',
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
