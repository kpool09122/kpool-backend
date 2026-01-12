<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $affiliation_id
 * @property string $policy_id
 * @property string $role_id
 * @property string $principal_group_id
 * @property string $type
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class AffiliationGrant extends Model
{
    public $incrementing = false;

    protected $table = 'affiliation_grants';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'affiliation_id',
        'policy_id',
        'role_id',
        'principal_group_id',
        'type',
    ];
}
