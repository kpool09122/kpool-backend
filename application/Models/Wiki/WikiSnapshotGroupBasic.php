<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $snapshot_id
 * @property string $name
 * @property string $normalized_name
 * @property ?string $agency_identifier
 * @property ?string $group_type
 * @property ?string $status
 * @property ?string $generation
 * @property ?string $debut_date
 * @property ?string $disband_date
 * @property string $fandom_name
 * @property array<string> $official_colors
 * @property string $emoji
 * @property string $representative_symbol
 * @property ?string $main_image_identifier
 */
class WikiSnapshotGroupBasic extends Model
{
    public $incrementing = false;

    protected $table = 'wiki_snapshot_group_basics';

    protected $primaryKey = 'snapshot_id';

    protected $keyType = 'string';

    protected $fillable = [
        'snapshot_id',
        'name',
        'normalized_name',
        'agency_identifier',
        'group_type',
        'status',
        'generation',
        'debut_date',
        'disband_date',
        'fandom_name',
        'official_colors',
        'emoji',
        'representative_symbol',
        'main_image_identifier',
    ];

    protected $casts = [
        'official_colors' => 'array',
    ];

    /**
     * @return BelongsTo<WikiSnapshot, WikiSnapshotGroupBasic>
     */
    public function wikiSnapshot(): BelongsTo
    {
        return $this->belongsTo(WikiSnapshot::class, 'snapshot_id', 'id');
    }
}
