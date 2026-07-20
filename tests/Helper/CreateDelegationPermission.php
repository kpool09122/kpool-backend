<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Account\DelegationPermission\Domain\ValueObject\DelegationPermissionIdentifier;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class CreateDelegationPermission
{
    public static function create(
        DelegationPermissionIdentifier $delegationPermissionIdentifier,
        PrincipalGroupIdentifier $principalGroupIdentifier,
        AccountIdentifier $targetAccountIdentifier,
        AffiliationIdentifier $affiliationIdentifier,
    ): void {
        DB::table('delegation_permissions')->insert([
            'id' => (string) $delegationPermissionIdentifier,
            'principal_group_id' => (string) $principalGroupIdentifier,
            'target_account_id' => (string) $targetAccountIdentifier,
            'affiliation_id' => (string) $affiliationIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
