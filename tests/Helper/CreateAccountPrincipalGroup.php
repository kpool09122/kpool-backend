<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class CreateAccountPrincipalGroup
{
    /**
     * @param array{
     *     name?: string,
     *     role?: string,
     *     is_default?: bool,
     * } $overrides
     */
    public static function create(
        PrincipalGroupIdentifier $principalGroupIdentifier,
        AccountIdentifier $accountIdentifier,
        array $overrides = []
    ): void {
        DB::table('account_principal_groups')->insert([
            'id' => (string) $principalGroupIdentifier,
            'account_id' => (string) $accountIdentifier,
            'name' => $overrides['name'] ?? 'Test Group',
            'role' => $overrides['role'] ?? 'owner',
            'is_default' => $overrides['is_default'] ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
