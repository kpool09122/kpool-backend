<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property array<array{effect: string, actions: list<string>, resource_types: list<string>, condition: string|null}> $statements
 */
class Policy extends Model
{
    protected $table = 'site_management_policies';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected $casts = ['statements' => 'array'];

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
