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
 * @property string $normalized_name
 * @property string $CEO
 * @property string $normalized_CEO
 * @property ?Carbon $founded_in
 * @property string $description
 * @property int $version
 * @property bool $is_official
 * @property ?string $owner_account_id
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
        'normalized_name',
        'CEO',
        'normalized_CEO',
        'founded_in',
        'description',
        'version',
        'is_official',
        'owner_account_id',
    ];

    protected $casts = [
        'founded_in' => 'date',
        'version' => 'integer',
        'is_official' => 'boolean',
    ];
}
