<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use JsonException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class CreatePrincipal
{
    /**
     * @param array{
     *     agency_id?: ?string,
     *     group_ids?: string[],
     *     talent_ids?: string[],
     *     delegation_identifier?: ?string,
     *     enabled?: bool
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
            'agency_id' => $overrides['agency_id'] ?? null,
            'group_ids' => json_encode($overrides['group_ids'] ?? [], JSON_THROW_ON_ERROR),
            'talent_ids' => json_encode($overrides['talent_ids'] ?? [], JSON_THROW_ON_ERROR),
            'delegation_identifier' => $overrides['delegation_identifier'] ?? null,
            'enabled' => $overrides['enabled'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
