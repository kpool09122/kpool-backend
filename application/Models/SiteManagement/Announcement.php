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
class Announcement extends Model
{
    public $incrementing = false;

    protected $table = 'announcements';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'translation_set_identifier',
        'language',
        'category',
        'title',
        'content',
        'published_date',
    ];

    protected $casts = [
        'category' => 'integer',
        'published_date' => 'datetime',
    ];
}
