<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $principal_group_id
 * @property string $principal_id
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class PrincipalGroupMembership extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected $table = 'principal_group_memberships';

    #[\Override]
    protected $primaryKey = ['principal_group_id', 'principal_id'];

    #[\Override]
    protected $keyType = 'string';

    #[\Override]
    protected $fillable = [
        'principal_group_id',
        'principal_id',
    ];

    #[\Override]
    public function getKey(): string
    {
        return $this->principal_group_id . '_' . $this->principal_id;
    }

    #[\Override]
    protected function setKeysForSaveQuery($query): mixed
    {
        return $query->where('principal_group_id', $this->principal_group_id)
            ->where('principal_id', $this->principal_id);
    }
}
