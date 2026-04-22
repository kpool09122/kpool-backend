<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property ?string $published_wiki_id
 * @property string $translation_set_identifier
 * @property string $slug
 * @property string $language
 * @property string $resource_type
 * @property array<array<string, mixed>> $sections
 * @property ?string $theme_color
 * @property string $status
 * @property ?string $editor_id
 * @property ?string $approver_id
 * @property ?string $merger_id
 * @property ?string $source_editor_id
 * @property ?Carbon $edited_at
 * @property ?Carbon $merged_at
 * @property ?Carbon $translated_at
 * @property ?Carbon $approved_at
 * @property ?Carbon $created_at
 * @property-read ?DraftWikiTalentBasic $talentBasic
 * @property-read ?DraftWikiGroupBasic $groupBasic
 * @property-read ?DraftWikiAgencyBasic $agencyBasic
 * @property-read ?DraftWikiSongBasic $songBasic
 * @property-read ?Wiki $publishedWiki
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'published_wiki_id',
    'translation_set_identifier',
    'slug',
    'language',
    'resource_type',
    'sections',
    'theme_color',
    'status',
    'editor_id',
    'approver_id',
    'merger_id',
    'source_editor_id',
    'edited_at',
    'merged_at',
    'translated_at',
    'approved_at',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'draft_wikis', keyType: 'string')]
class DraftWiki extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected $casts = [
        'sections' => 'array',
        'edited_at' => 'datetime',
        'merged_at' => 'datetime',
        'approved_at' => 'datetime',
        'translated_at' => 'datetime',
    ];

    /**
     * @return HasOne<DraftWikiTalentBasic>
     */
    public function talentBasic(): HasOne
    {
        return $this->hasOne(DraftWikiTalentBasic::class, 'wiki_id', 'id');
    }

    /**
     * @return HasOne<DraftWikiGroupBasic>
     */
    public function groupBasic(): HasOne
    {
        return $this->hasOne(DraftWikiGroupBasic::class, 'wiki_id', 'id');
    }

    /**
     * @return HasOne<DraftWikiAgencyBasic>
     */
    public function agencyBasic(): HasOne
    {
        return $this->hasOne(DraftWikiAgencyBasic::class, 'wiki_id', 'id');
    }

    /**
     * @return HasOne<DraftWikiSongBasic>
     */
    public function songBasic(): HasOne
    {
        return $this->hasOne(DraftWikiSongBasic::class, 'wiki_id', 'id');
    }

    /**
     * @return BelongsTo<Wiki, DraftWiki>
     */
    public function publishedWiki(): BelongsTo
    {
        return $this->belongsTo(Wiki::class, 'published_wiki_id', 'id');
    }
}
