<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;

class CreatePrincipalGroup
{
    /**
     * @param array{
     *     name?: string,
     *     is_default?: bool,
     * } $overrides
     */
    public static function create(
        PrincipalGroupIdentifier $principalGroupIdentifier,
        AccountIdentifier $accountIdentifier,
        array $overrides = []
    ): void {
        DB::table('principal_groups')->insert([
            'id' => (string) $principalGroupIdentifier,
            'account_id' => (string) $accountIdentifier,
            'name' => $overrides['name'] ?? 'Test Group',
            'is_default' => $overrides['is_default'] ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
