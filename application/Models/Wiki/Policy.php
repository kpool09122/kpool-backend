<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property array<array{effect: string, actions: array<string>, resource_types: array<string>, condition: array<array{key: string, operator: string, value: string}>|null}> $statements
 * @property bool $is_system_policy
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class Policy extends Model
{
    public $incrementing = false;

    protected $table = 'policies';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'statements',
        'is_system_policy',
    ];

    protected function casts(): array
    {
        return [
            'statements' => 'array',
            'is_system_policy' => 'boolean',
        ];
    }
}
