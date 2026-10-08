<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string $id
 * @property ?string $account_id
 * @property string $name
 * @property array<array{effect: string, actions: list<string>, resource_types: list<string>, condition: string|null}> $statements
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
#[Fillable([
    'id',
    'account_id',
    'name',
    'statements',
])]
#[Table(name: 'site_management_policies', keyType: 'string')]
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

    /** @return list<array{effect: string, actions: list<string>, resourceTypes: list<string>, condition: string|null}> */
    public function statementValues(): array
    {
        return array_map(static fn (array $statement): array => [
            'effect' => $statement['effect'],
            'actions' => array_values($statement['actions']),
            'resourceTypes' => array_values($statement['resource_types']),
            'condition' => $statement['condition'],
        ], array_values($this->statements));
    }
}
