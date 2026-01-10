<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property bool $is_system_role
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class Role extends Model
{
    public $incrementing = false;

    protected $table = 'roles';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'is_system_role',
    ];

    protected function casts(): array
    {
        return [
            'is_system_role' => 'boolean',
        ];
    }
}
