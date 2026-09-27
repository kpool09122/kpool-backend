<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string $id
 * @property string $identity_id
 * @property string $role
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
#[Fillable([
    'id',
    'identity_id',
    'role',
])]
#[Table(name: 'site_management_users', keyType: 'string')]
class User extends Model
{
    #[Override]
    public $incrementing = false;
}
