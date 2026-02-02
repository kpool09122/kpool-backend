<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $snapshot_id
 * @property string $name
 * @property string $normalized_name
 * @property string $ceo
 * @property string $normalized_ceo
 * @property ?string $founded_in
 * @property ?string $parent_agency_identifier
 * @property ?string $status
 * @property ?string $logo_image_identifier
 * @property ?string $official_website
 * @property array<string> $social_links
 */
class WikiSnapshotAgencyBasic extends Model
{
    public $incrementing = false;

    protected $table = 'wiki_snapshot_agency_basics';

    protected $primaryKey = 'snapshot_id';

    protected $keyType = 'string';

    protected $fillable = [
        'snapshot_id',
        'name',
        'normalized_name',
        'ceo',
        'normalized_ceo',
        'founded_in',
        'parent_agency_identifier',
        'status',
        'logo_image_identifier',
        'official_website',
        'social_links',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    /**
     * @return BelongsTo<WikiSnapshot, WikiSnapshotAgencyBasic>
     */
    public function wikiSnapshot(): BelongsTo
    {
        return $this->belongsTo(WikiSnapshot::class, 'snapshot_id', 'id');
    }
}
