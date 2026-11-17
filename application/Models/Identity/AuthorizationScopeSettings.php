<?php

declare(strict_types=1);

namespace Application\Models\Identity;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property string $id
 * @property string $identity_id
 * @property list<string> $scopes
 */
#[Fillable(['id', 'identity_id', 'scopes'])]
#[Table(name: 'identity_authorization_scope_settings', key: 'id', keyType: 'string')]
class AuthorizationScopeSettings extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    protected function casts(): array
    {
        return ['scopes' => 'array'];
    }
}
