<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $identity_id
 * @property string $role
 * @property ?string $agency_id
 * @property array<string> $group_ids
 * @property array<string> $talent_ids
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class Principal extends Model
{
    public $incrementing = false;

    protected $table = 'wiki_principals';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'identity_id',
        'role',
        'agency_id',
        'group_ids',
        'talent_ids',
    ];

    protected function casts(): array
    {
        return [
            'group_ids' => 'array',
            'talent_ids' => 'array',
        ];
    }
}
