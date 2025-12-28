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
class User extends Model
{
    public $incrementing = false;

    protected $table = 'site_management_users';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'identity_id',
        'role',
    ];
}
