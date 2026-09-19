<?php

declare(strict_types=1);

namespace Application\Models\Identity;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $identity_id
 * @property string $credential_id
 * @property string $credential_source
 * @property int $sign_count
 * @property bool $backup_eligible
 * @property bool $backup_state
 * @property array<int, string> $transports
 * @property string $display_name
 * @property ?Carbon $last_used_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
#[Fillable(['id', 'identity_id', 'credential_id', 'credential_source', 'sign_count', 'backup_eligible', 'backup_state', 'transports', 'display_name', 'last_used_at'])]
#[Table(name: 'identity_passkey_credentials', keyType: 'string')]
class PasskeyCredential extends Model
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected function casts(): array
    {
        return [
            'sign_count' => 'integer',
            'backup_eligible' => 'boolean',
            'backup_state' => 'boolean',
            'transports' => 'array',
            'last_used_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Identity, $this> */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_id', 'id');
    }
}
