<?php

declare(strict_types=1);

namespace Application\Models\Identity;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string $id
 * @property string $identity_id
 * @property string $client_id
 * @property list<string> $scopes
 * @property string $original_account_id
 * @property string $original_principal_id
 * @property string $resource
 * @property Carbon $consented_at
 * @property-read ?McpClient $client
 * @property ?Carbon $revoked_at
 */
#[Fillable([
    'id',
    'identity_id',
    'client_id',
    'scopes',
    'original_account_id',
    'original_principal_id',
    'resource',
    'consented_at',
    'revoked_at',
])]
#[Table(name: 'identity_authorization_consents', keyType: 'string')]
class AuthorizationConsent extends Model
{
    #[Override]
    public $incrementing = false;

    /** @return BelongsTo<McpClient, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(McpClient::class, 'client_id');
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'consented_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
