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
     *     account_id?: string|null,
     *     policies?: string[],
     * } $overrides
     */
    public static function create(
        RoleIdentifier $roleIdentifier,
        array $overrides = []
    ): void {
        DB::table('wiki_roles')->insert([
            'id' => (string) $roleIdentifier,
            'name' => $overrides['name'] ?? 'Test Role ' . (string) $roleIdentifier,
            'account_id' => $overrides['account_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // role_policy_attachments にアタッチ
        if (isset($overrides['policies'])) {
            foreach ($overrides['policies'] as $policyId) {
                DB::table('wiki_role_policy_attachments')->insert([
                    'role_id' => (string) $roleIdentifier,
                    'policy_id' => $policyId,
                ]);
            }
        }
    }
}
