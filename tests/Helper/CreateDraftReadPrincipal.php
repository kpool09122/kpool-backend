<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

final class CreateDraftReadPrincipal
{
    public static function create(
        bool $allow = true,
        ?string $conditionKey = null,
        ?string $conditionValue = null,
        bool $deny = false,
        ?IdentityIdentifier $identityIdentifier = null,
        ?AccountIdentifier $accountIdentifier = null,
        ?string $delegationIdentifier = null,
    ): PrincipalIdentifier {
        if ($identityIdentifier === null) {
            $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
            CreateIdentity::create($identityIdentifier, ['email' => (string) $identityIdentifier . '@example.com']);
        }
        if ($accountIdentifier === null) {
            $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
            CreateAccount::create((string) $accountIdentifier);
        }
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier, $identityIdentifier, $accountIdentifier, [
            'delegation_identifier' => $delegationIdentifier,
        ]);
        if (! $allow) {
            return $principalIdentifier;
        }

        $condition = $conditionKey === null ? null : [[
            'key' => $conditionKey,
            'operator' => 'eq',
            'value' => $conditionValue ?? '',
        ]];
        $statements = [[
            'effect' => 'allow',
            'actions' => ['read'],
            'resource_types' => ['agency', 'group', 'song', 'talent'],
            'condition' => $condition,
        ]];
        if ($deny) {
            $statements[] = [
                'effect' => 'deny',
                'actions' => ['read'],
                'resource_types' => ['agency', 'group', 'song', 'talent'],
                'condition' => null,
            ];
        }
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        CreatePolicy::create($policyIdentifier, ['statements' => $statements]);
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        CreateRole::create($roleIdentifier, ['policies' => [(string) $policyIdentifier]]);
        $groupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        CreatePrincipalGroup::create($groupIdentifier, $accountIdentifier);
        CreatePrincipalGroupMembership::create((string) $groupIdentifier, (string) $principalIdentifier);
        DB::table('wiki_principal_group_role_attachments')->insert([
            'principal_group_id' => (string) $groupIdentifier,
            'role_id' => (string) $roleIdentifier,
        ]);

        return $principalIdentifier;
    }
}
