<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;

class CreatePolicy
{
    /**
     * @param array{
     *     name?: string,
     *     statements?: array<array{effect: string, actions: array<string>, resource_types: array<string>, condition: array<array{key: string, operator: string, value: string|bool}>|null}>,
     *     is_system_policy?: bool,
     * } $overrides
     */
    public static function create(
        PolicyIdentifier $policyIdentifier,
        array $overrides = []
    ): void {
        DB::table('policies')->insert([
            'id' => (string) $policyIdentifier,
            'name' => $overrides['name'] ?? 'Test Policy',
            'statements' => json_encode($overrides['statements'] ?? [
                [
                    'effect' => 'allow',
                    'actions' => ['create'],
                    'resource_types' => ['talent'],
                    'condition' => null,
                ],
            ]),
            'is_system_policy' => $overrides['is_system_policy'] ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
