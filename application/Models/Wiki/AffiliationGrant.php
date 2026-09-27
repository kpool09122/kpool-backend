<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

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
#[Fillable([
    'id',
    'affiliation_id',
    'policy_id',
    'role_id',
    'principal_group_id',
    'type',
])]
#[Table(name: 'affiliation_grants', keyType: 'string')]
class AffiliationGrant extends Model
{
    #[Override]
    public $incrementing = false;
}
