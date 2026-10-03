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
     *     account_id?: string|null,
     * } $overrides
     */
    public static function create(
        PolicyIdentifier $policyIdentifier,
        array $overrides = []
    ): void {
        DB::table('wiki_policies')->insert([
            'id' => (string) $policyIdentifier,
            'name' => $overrides['name'] ?? 'Test Policy ' . (string) $policyIdentifier,
            'statements' => json_encode($overrides['statements'] ?? [
                [
                    'effect' => 'allow',
                    'actions' => ['create'],
                    'resource_types' => ['talent'],
                    'condition' => null,
                ],
            ]),
            'account_id' => $overrides['account_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
