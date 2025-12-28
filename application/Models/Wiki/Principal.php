<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $identity_id
 * @property string $role
 * @property ?string $agency_id
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
        'talent_ids',
    ];

    protected function casts(): array
    {
        return [
            'talent_ids' => 'array',
        ];
    }

    /**
     * @return BelongsToMany<Group, $this>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            Group::class,
            'wiki_principal_groups',
            'wiki_principal_id',
            'group_id',
        );
    }
}
