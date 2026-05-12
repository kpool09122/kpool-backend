<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property ?string $published_id
 * @property string $resource_type
 * @property string $translation_set_identifier
 * @property string $uploader_id
 * @property string $image_path
 * @property string $image_usage
 * @property int $display_order
 * @property string $source_url
 * @property string $source_name
 * @property string $alt_text
 * @property string $status
 * @property Carbon $agreed_to_terms_at
 * @property bool $rights_confirmation_agreed
 * @property Carbon $uploaded_at
 * @property-read Collection<int, Wiki> $wikis
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'published_id',
    'resource_type',
    'translation_set_identifier',
    'uploader_id',
    'image_path',
    'image_usage',
    'display_order',
    'source_url',
    'source_name',
    'alt_text',
    'status',
    'agreed_to_terms_at',
    'rights_confirmation_agreed',
    'uploaded_at',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'draft_wiki_images', keyType: 'string')]
class DraftWikiImage extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    public $timestamps = false;

    #[\Override]
    protected $casts = [
        'display_order' => 'integer',
        'agreed_to_terms_at' => 'datetime',
        'rights_confirmation_agreed' => 'boolean',
        'uploaded_at' => 'datetime',
    ];

    /**
     * @return HasMany<Wiki>
     */
    public function wikis(): HasMany
    {
        return $this->hasMany(Wiki::class, 'translation_set_identifier', 'translation_set_identifier');
    }
}
