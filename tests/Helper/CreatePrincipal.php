<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use JsonException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;

class CreatePrincipal
{
    /**
     * @param array{
     *     role?: Role,
     *     agency_id?: ?string,
     *     group_ids?: string[],
     *     talent_ids?: string[]
     * } $overrides
     * @throws JsonException
     */
    public static function create(
        PrincipalIdentifier $principalIdentifier,
        IdentityIdentifier $identityIdentifier,
        array $overrides = []
    ): void {
        DB::table('wiki_principals')->insert([
            'id' => (string) $principalIdentifier,
            'identity_id' => (string) $identityIdentifier,
            'role' => ($overrides['role'] ?? Role::ADMINISTRATOR)->value,
            'agency_id' => $overrides['agency_id'] ?? null,
            'talent_ids' => json_encode($overrides['talent_ids'] ?? [], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $groupIds = $overrides['group_ids'] ?? [];
        foreach ($groupIds as $groupId) {
            DB::table('wiki_principal_groups')->insert([
                'wiki_principal_id' => (string) $principalIdentifier,
                'group_id' => $groupId,
            ]);
        }
    }
}
