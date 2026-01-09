<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class CreateIdentityGroup
{
    /**
     * @param array{
     *     name?: string,
     *     role?: string,
     *     is_default?: bool,
     * } $overrides
     */
    public static function create(
        IdentityGroupIdentifier $identityGroupIdentifier,
        AccountIdentifier $accountIdentifier,
        array $overrides = []
    ): void {
        DB::table('identity_groups')->insert([
            'id' => (string) $identityGroupIdentifier,
            'account_id' => (string) $accountIdentifier,
            'name' => $overrides['name'] ?? 'Test Group',
            'role' => $overrides['role'] ?? 'owner',
            'is_default' => $overrides['is_default'] ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
