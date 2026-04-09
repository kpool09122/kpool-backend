<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $identity_id
 * @property string $role
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'id',
    'identity_id',
    'role',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'site_management_users', keyType: 'string')]
class User extends Model
{
    #[\Override]
    public $incrementing = false;
}
