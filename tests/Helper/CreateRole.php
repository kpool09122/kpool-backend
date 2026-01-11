<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

class CreateRole
{
    /**
     * @param array{
     *     name?: string,
     *     is_system_role?: bool,
     *     policies?: string[],
     * } $overrides
     */
    public static function create(
        RoleIdentifier $roleIdentifier,
        array $overrides = []
    ): void {
        DB::table('roles')->insert([
            'id' => (string) $roleIdentifier,
            'name' => $overrides['name'] ?? 'Test Role',
            'is_system_role' => $overrides['is_system_role'] ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // role_policy_attachments にアタッチ
        if (isset($overrides['policies'])) {
            foreach ($overrides['policies'] as $policyId) {
                DB::table('role_policy_attachments')->insert([
                    'role_id' => (string) $roleIdentifier,
                    'policy_id' => $policyId,
                ]);
            }
        }
    }
}
