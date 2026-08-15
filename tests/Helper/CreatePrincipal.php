<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class CreatePrincipal
{
    /**
     * @param array{
     *     delegation_identifier?: ?string,
     *     enabled?: bool
     * } $overrides
     */
    public static function create(
        PrincipalIdentifier $principalIdentifier,
        IdentityIdentifier $identityIdentifier,
        array $overrides = []
    ): void {
        DB::table('wiki_principals')->insert([
            'id' => (string) $principalIdentifier,
            'identity_id' => (string) $identityIdentifier,
            'delegation_identifier' => $overrides['delegation_identifier'] ?? null,
            'enabled' => $overrides['enabled'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
