<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $translation_set_identifier
 * @property string $language
 * @property string $name
 * @property string $CEO
 * @property ?Carbon $founded_in
 * @property string $description
 * @property int $version
 */
class Agency extends Model
{
    public $incrementing = false;

    protected $table = 'agencies';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'translation_set_identifier',
        'language',
        'name',
        'CEO',
        'founded_in',
        'description',
        'version',
    ];

    protected $casts = [
        'founded_in' => 'date',
        'version' => 'integer',
    ];
}
