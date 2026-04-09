<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $translation_set_identifier
 * @property string $slug
 * @property string $language
 * @property string $resource_type
 * @property array<array<string, mixed>> $sections
 * @property ?string $theme_color
 * @property int $version
 * @property ?string $owner_account_id
 * @property ?string $editor_id
 * @property ?string $approver_id
 * @property ?string $merger_id
 * @property ?string $source_editor_id
 * @property ?Carbon $merged_at
 * @property ?Carbon $translated_at
 * @property ?Carbon $approved_at
 * @property ?Carbon $published_at
 * @property-read ?WikiTalentBasic $talentBasic
 * @property-read ?WikiGroupBasic $groupBasic
 * @property-read ?WikiAgencyBasic $agencyBasic
 * @property-read ?WikiSongBasic $songBasic
 */
class Wiki extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected $table = 'wikis';

    #[\Override]
    protected $keyType = 'string';

    #[\Override]
    protected $fillable = [
        'id',
        'translation_set_identifier',
        'slug',
        'language',
        'resource_type',
        'sections',
        'theme_color',
        'version',
        'owner_account_id',
        'editor_id',
        'approver_id',
        'merger_id',
        'source_editor_id',
        'merged_at',
        'translated_at',
        'approved_at',
        'published_at',
    ];

    #[\Override]
    protected $casts = [
        'sections' => 'array',
        'version' => 'integer',
        'merged_at' => 'datetime',
        'approved_at' => 'datetime',
        'translated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    /**
     * @return HasOne<WikiTalentBasic>
     */
    public function talentBasic(): HasOne
    {
        return $this->hasOne(WikiTalentBasic::class, 'wiki_id', 'id');
    }

    /**
     * @return HasOne<WikiGroupBasic>
     */
    public function groupBasic(): HasOne
    {
        return $this->hasOne(WikiGroupBasic::class, 'wiki_id', 'id');
    }

    /**
     * @return HasOne<WikiAgencyBasic>
     */
    public function agencyBasic(): HasOne
    {
        return $this->hasOne(WikiAgencyBasic::class, 'wiki_id', 'id');
    }

    /**
     * @return HasOne<WikiSongBasic>
     */
    public function songBasic(): HasOne
    {
        return $this->hasOne(WikiSongBasic::class, 'wiki_id', 'id');
    }
}
