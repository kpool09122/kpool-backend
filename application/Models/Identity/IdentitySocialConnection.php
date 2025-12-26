<?php

declare(strict_types=1);

namespace Application\Models\Identity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $user_id
 * @property string $provider
 * @property string $provider_user_id
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Identity $identity
 */
class IdentitySocialConnection extends Model
{
    protected $table = 'user_social_connections';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
    ];

    /**
     * @return BelongsTo<Identity, $this>
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'user_id', 'id');
    }
}
