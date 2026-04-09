<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $wiki_id
 * @property string $translation_set_identifier
 * @property string $slug
 * @property string $language
 * @property string $resource_type
 * @property array<array<string, mixed>> $sections
 * @property ?string $theme_color
 * @property int $version
 * @property ?string $editor_id
 * @property ?string $merger_id
 * @property ?Carbon $merged_at
 * @property ?string $approver_id
 * @property ?Carbon $approved_at
 * @property ?string $source_editor_id
 * @property ?Carbon $translated_at
 * @property ?Carbon $created_at
 * @property-read ?WikiSnapshotTalentBasic $talentBasic
 * @property-read ?WikiSnapshotGroupBasic $groupBasic
 * @property-read ?WikiSnapshotAgencyBasic $agencyBasic
 * @property-read ?WikiSnapshotSongBasic $songBasic
 */
class WikiSnapshot extends Model
{
    #[\Override]
    public $incrementing = false;

    public const UPDATED_AT = null;

    #[\Override]
    protected $table = 'wiki_snapshots';

    #[\Override]
    protected $keyType = 'string';

    #[\Override]
    protected $fillable = [
        'id',
        'wiki_id',
        'translation_set_identifier',
        'slug',
        'language',
        'resource_type',
        'sections',
        'theme_color',
        'version',
        'editor_id',
        'merger_id',
        'merged_at',
        'approver_id',
        'approved_at',
        'source_editor_id',
        'translated_at',
    ];

    #[\Override]
    protected $casts = [
        'sections' => 'array',
        'version' => 'integer',
        'merged_at' => 'datetime',
        'approved_at' => 'datetime',
        'translated_at' => 'datetime',
    ];

    /**
     * @return HasOne<WikiSnapshotTalentBasic>
     */
    public function talentBasic(): HasOne
    {
        return $this->hasOne(WikiSnapshotTalentBasic::class, 'snapshot_id', 'id');
    }

    /**
     * @return HasOne<WikiSnapshotGroupBasic>
     */
    public function groupBasic(): HasOne
    {
        return $this->hasOne(WikiSnapshotGroupBasic::class, 'snapshot_id', 'id');
    }

    /**
     * @return HasOne<WikiSnapshotAgencyBasic>
     */
    public function agencyBasic(): HasOne
    {
        return $this->hasOne(WikiSnapshotAgencyBasic::class, 'snapshot_id', 'id');
    }

    /**
     * @return HasOne<WikiSnapshotSongBasic>
     */
    public function songBasic(): HasOne
    {
        return $this->hasOne(WikiSnapshotSongBasic::class, 'snapshot_id', 'id');
    }
}
