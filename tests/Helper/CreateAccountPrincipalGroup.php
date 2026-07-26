<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class CreateAccountPrincipalGroup
{
    /**
     * @param array{
     *     name?: string,
     *     role_ids?: string[],
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
            'is_default' => $overrides['is_default'] ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($overrides['role_ids'] ?? [] as $roleId) {
            DB::table('account_principal_group_role_attachments')->insert([
                'principal_group_id' => (string) $principalGroupIdentifier,
                'role_id' => $roleId,
            ]);
        }
    }

    public static function attachRole(
        PrincipalGroupIdentifier $principalGroupIdentifier,
        RoleIdentifier $roleIdentifier,
    ): void {
        DB::table('account_principal_group_role_attachments')->insert([
            'principal_group_id' => (string) $principalGroupIdentifier,
            'role_id' => (string) $roleIdentifier,
        ]);
    }
}
