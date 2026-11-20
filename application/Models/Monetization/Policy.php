<?php

declare(strict_types=1);

namespace Application\Models\Monetization;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string $id
 * @property ?string $account_id
 * @property string $name
 * @property array<array{effect: string, actions: list<string>, resource_types: list<string>, condition?: list<array{key: string, operator: string, value: string|list<string>}>|null}> $statements
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
#[Fillable([
    'id',
    'account_id',
    'name',
    'statements',
])]
#[Table(name: 'monetization_policies', keyType: 'string')]
class Policy extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    protected function casts(): array
    {
        return [
            'statements' => 'array',
        ];
    }
}
