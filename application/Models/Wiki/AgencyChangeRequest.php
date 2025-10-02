<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property ?string $published_id
 * @property string $translation_set_identifier
 * @property string $editor_id
 * @property string $translation
 * @property string $name
 * @property string $CEO
 * @property ?Carbon $founded_in
 * @property string $description
 * @property string $status
 */
class AgencyChangeRequest extends Model
{
    public $incrementing = false;

    protected $table = 'agencies_pending';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'published_id',
        'translation_set_identifier',
        'editor_id',
        'translation',
        'name',
        'CEO',
        'founded_in',
        'description',
        'status',
    ];

    protected $casts = [
        'founded_in' => 'date',
    ];
}
