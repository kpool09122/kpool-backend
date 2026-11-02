<?php

declare(strict_types=1);

namespace Application\Models\Analyze;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string $id
 * @property ?string $account_id
 * @property string $name
 * @property array<array{effect: string, actions: list<string>, resource_types: list<string>}> $statements
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
#[Fillable([
    'id',
    'account_id',
    'name',
    'statements',
])]
#[Table(name: 'analyze_policies', keyType: 'string')]
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

    /** @return list<array{effect: string, actions: list<string>, resourceTypes: list<string>}> */
    public function statementValues(): array
    {
        return array_map(static fn (array $statement): array => [
            'effect' => $statement['effect'],
            'actions' => array_values($statement['actions']),
            'resourceTypes' => array_values($statement['resource_types']),
        ], array_values($this->statements));
    }
}
