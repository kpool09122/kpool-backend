<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $snapshot_id
 * @property string $name
 * @property string $normalized_name
 * @property string $real_name
 * @property string $normalized_real_name
 * @property ?string $birthday
 * @property ?string $agency_identifier
 * @property ?string $emoji
 * @property ?string $representative_symbol
 * @property ?string $position
 * @property ?string $mbti
 * @property ?string $zodiac_sign
 * @property ?string $english_level
 * @property ?string $height
 * @property ?string $blood_type
 * @property ?string $fandom_name
 * @property ?string $profile_image_identifier
 * @property-read Collection<int, Wiki> $groups
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'snapshot_id',
    'name',
    'normalized_name',
    'real_name',
    'normalized_real_name',
    'birthday',
    'agency_identifier',
    'emoji',
    'representative_symbol',
    'position',
    'mbti',
    'zodiac_sign',
    'english_level',
    'height',
    'blood_type',
    'fandom_name',
    'profile_image_identifier',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'wiki_snapshot_talent_basics', key: 'snapshot_id', keyType: 'string')]
class WikiSnapshotTalentBasic extends Model
{
    #[\Override]
    public $incrementing = false;

    /**
     * @return BelongsTo<WikiSnapshot, WikiSnapshotTalentBasic>
     */
    public function wikiSnapshot(): BelongsTo
    {
        return $this->belongsTo(WikiSnapshot::class, 'snapshot_id', 'id');
    }

    /**
     * @return BelongsToMany<Wiki>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Wiki::class, 'wiki_snapshot_talent_basic_groups', 'snapshot_id', 'group_identifier');
    }
}
