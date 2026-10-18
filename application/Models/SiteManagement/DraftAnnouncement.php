<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string      $id
 * @property string      $source_language
 * @property int         $category
 * @property array<string, array{title: string, content: string}> $contents
 * @property Carbon|null $published_date
 */
#[Fillable([
    'id',
    'source_language',
    'category',
    'contents',
    'published_date',
])]
#[Table(name: 'draft_announcements', keyType: 'string')]
class DraftAnnouncement extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    protected $casts = [
        'category' => 'integer',
        'contents' => 'array',
        'published_date' => 'datetime',
    ];
}
