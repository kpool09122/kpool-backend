<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $wiki_id
 * @property string $name
 * @property string $normalized_name
 * @property string $real_name
 * @property string $normalized_real_name
 * @property ?string $birthday
 * @property ?string $agency_identifier
 * @property array<string> $group_identifiers
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
 */
class WikiTalentBasic extends Model
{
    public $incrementing = false;

    protected $table = 'wiki_talent_basics';

    protected $primaryKey = 'wiki_id';

    protected $keyType = 'string';

    protected $fillable = [
        'wiki_id',
        'name',
        'normalized_name',
        'real_name',
        'normalized_real_name',
        'birthday',
        'agency_identifier',
        'group_identifiers',
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
    ];

    protected $casts = [
        'group_identifiers' => 'array',
    ];

    /**
     * @return BelongsTo<Wiki, WikiTalentBasic>
     */
    public function wiki(): BelongsTo
    {
        return $this->belongsTo(Wiki::class, 'wiki_id', 'id');
    }
}
