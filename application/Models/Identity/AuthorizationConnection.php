<?php

declare(strict_types=1);

namespace Application\Models\Identity;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string $id
 * @property string $identity_id
 * @property string $client_id
 * @property list<string> $scopes
 * @property list<array{account_id: string, delegation_id: ?string}> $targets
 * @property string $original_account_id
 * @property string $original_principal_id
 * @property string $initial_account_id
 * @property string $resource
 * @property Carbon $consented_at
 * @property ?Carbon $revoked_at
 */
#[Table(name: 'identity_authorization_connections', keyType: 'string')]
class AuthorizationConnection extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'targets' => 'array',
            'consented_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
