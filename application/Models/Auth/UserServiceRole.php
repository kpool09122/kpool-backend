<?php

declare(strict_types=1);

namespace Application\Models\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $user_id
 * @property string $service
 * @property string $role
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read User $user
 */
class UserServiceRole extends Model
{
    protected $table = 'user_service_roles';

    protected $fillable = [
        'user_id',
        'service',
        'role',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
