<?php

declare(strict_types=1);

namespace Application\Models\Identity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $identity_id
 * @property string $provider
 * @property string $provider_user_id
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Identity $identity
 */
class IdentitySocialConnection extends Model
{
    public $incrementing = false;

    protected $table = 'identity_social_connections';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'identity_id',
        'provider',
        'provider_user_id',
    ];

    /**
     * @return BelongsTo<Identity, $this>
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_id', 'id');
    }
}
