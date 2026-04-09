<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string      $id
 * @property string      $translation_set_identifier
 * @property string      $language
 * @property int         $category
 * @property string      $title
 * @property string      $content
 * @property Carbon|null $published_date
 */
class DraftAnnouncement extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected $table = 'draft_announcements';

    #[\Override]
    protected $keyType = 'string';

    #[\Override]
    protected $fillable = [
        'id',
        'translation_set_identifier',
        'language',
        'category',
        'title',
        'content',
        'published_date',
    ];

    #[\Override]
    protected $casts = [
        'category' => 'integer',
        'published_date' => 'datetime',
    ];
}
