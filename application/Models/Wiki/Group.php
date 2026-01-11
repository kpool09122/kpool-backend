<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $translation_set_identifier
 * @property string $translation
 * @property string $name
 * @property string $normalized_name
 * @property ?string $agency_id
 * @property string $description
 * @property ?string $image_path
 * @property int $version
 * @property bool $is_official
 * @property ?string $owner_account_id
 */
class Group extends Model
{
    public $incrementing = false;

    protected $table = 'groups';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'translation_set_identifier',
        'translation',
        'name',
        'normalized_name',
        'agency_id',
        'description',
        'image_path',
        'version',
        'is_official',
        'owner_account_id',
    ];

    protected $casts = [
        'version' => 'integer',
        'is_official' => 'boolean',
    ];
}
