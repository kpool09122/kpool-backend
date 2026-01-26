<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $translation_set_identifier
 * @property string $slug
 * @property string $translation
 * @property string $name
 * @property string $normalized_name
 * @property ?string $agency_id
 * @property string $description
 * @property ?string $image_path
 * @property ?string $editor_id
 * @property ?string $approver_id
 * @property ?string $merger_id
 * @property ?Carbon $merged_at
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
        'slug',
        'translation',
        'name',
        'normalized_name',
        'agency_id',
        'description',
        'image_path',
        'editor_id',
        'approver_id',
        'merger_id',
        'merged_at',
        'version',
        'is_official',
        'owner_account_id',
    ];

    protected $casts = [
        'merged_at' => 'datetime',
        'version' => 'integer',
        'is_official' => 'boolean',
    ];
}
