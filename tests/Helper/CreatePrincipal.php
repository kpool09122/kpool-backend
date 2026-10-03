<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class CreatePrincipal
{
    /**
     * @param AccountIdentifier|array{
     *     delegation_identifier?: ?string,
     *     enabled?: bool
     * }|null $accountIdentifier
     * @param array{
     *     delegation_identifier?: ?string,
     *     enabled?: bool
     * } $overrides
     */
    public static function create(
        PrincipalIdentifier $principalIdentifier,
        IdentityIdentifier $identityIdentifier,
        AccountIdentifier|array|null $accountIdentifier = null,
        array $overrides = []
    ): void {
        if (is_array($accountIdentifier)) {
            $overrides = $accountIdentifier;
            $accountIdentifier = null;
        }

        if ($accountIdentifier === null) {
            $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
            CreateAccount::create((string) $accountIdentifier);
        }

        DB::table('wiki_principals')->insert([
            'id' => (string) $principalIdentifier,
            'identity_id' => (string) $identityIdentifier,
            'account_id' => (string) $accountIdentifier,
            'delegation_identifier' => $overrides['delegation_identifier'] ?? null,
            'enabled' => $overrides['enabled'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
