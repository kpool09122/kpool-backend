<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string $id
 * @property ?string $account_id
 * @property string $name
 * @property array<array{effect: string, actions: array<string>, resource_types: array<string>}> $statements
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
#[Fillable([
    'id',
    'name',
    'account_id',
    'statements',
])]
#[Table(name: 'account_policies', keyType: 'string')]
class Policy extends Model
{
    public $incrementing = false;

    #[Override]
    protected function casts(): array
    {
        return [
            'statements' => 'array',
        ];
    }
}
