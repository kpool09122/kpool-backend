<?php

declare(strict_types=1);

namespace Tests\Helper;

use Application\Models\Account\Delegation;
use Database\Seeders\AccountAuthorizationSeeder;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Source\Account\Account\Application\UseCase\Command\SwitchAccount\SwitchAccountInput;
use Source\Account\Account\Application\UseCase\Command\SwitchAccount\SwitchAccountInterface;
use Source\Account\Account\Application\UseCase\Command\SwitchAccount\SwitchAccountOutput;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Identity\Infrastructure\Factory\AuthorizationConsentFactory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;

class CreateDelegatedApiAuthenticationContext
{
    /** @return array{ApiAuthenticationContext, Delegation} */
    public static function create(): array
    {
        app()->make(AccountAuthorizationSeeder::class)->run();
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        $original = new AccountIdentifier(StrTestHelper::generateUuid());
        $target = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $original);
        CreateAccount::create((string) $target, ['email' => 'target@example.test']);
        $principal = StrTestHelper::generateUuid();
        DB::table('account_principals')->insert(['id' => $principal, 'identity_id' => (string) $identity, 'account_id' => (string) $original]);
        $group = StrTestHelper::generateUuid();
        DB::table('account_principal_groups')->insert(['id' => $group, 'account_id' => (string) $original, 'name' => 'owner', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('account_principal_group_memberships')->insert(['id' => StrTestHelper::generateUuid(), 'principal_group_id' => $group, 'principal_id' => $principal]);
        $role = DB::table('account_roles')->where('name', 'Owner')->value('id');
        $policy = StrTestHelper::generateUuid();
        DB::table('account_policies')->insert(['id' => $policy, 'account_id' => (string) $original, 'name' => 'switch', 'created_at' => now(), 'updated_at' => now(), 'statements' => json_encode([['effect' => 'allow', 'actions' => ['account:delegation-account:switch'], 'resource_types' => ['account'], 'condition' => null]])]);
        DB::table('account_role_policy_attachments')->insert(['role_id' => $role, 'policy_id' => $policy]);
        DB::table('account_principal_group_role_attachments')->insert(['principal_group_id' => $group, 'role_id' => $role]);
        $delegation = Delegation::query()->create(['id' => StrTestHelper::generateUuid(), 'affiliation_id' => StrTestHelper::generateUuid(), 'delegate_account_id' => (string) $original, 'delegator_account_id' => (string) $target, 'requested_by_account_id' => (string) $original, 'status' => 'approved', 'direction' => 'from_agency', 'requested_at' => now(), 'approved_at' => now()]);
        $consent = new AuthorizationConsentFactory(new UuidGenerator())->create(
            $identity,
            new OAuthClientIdentifier(StrTestHelper::generateUuid()),
            [AuthorizationScope::WikiRead],
            $original,
            new AccountPrincipalIdentifier($principal),
            'https://mcp.test/mcp',
            new DateTimeImmutable(),
        );

        return [CreateApiAuthenticationContext::fromConsent($consent), $delegation];
    }

    public static function switchTo(ApiAuthenticationContext $context, ?DelegationIdentifier $delegationIdentifier): void
    {
        app(SwitchAccountInterface::class)->process(
            new SwitchAccountInput($context->identityIdentifier(), new PrincipalIdentifier((string) $context->originalPrincipalIdentifier()), $delegationIdentifier),
            new SwitchAccountOutput(),
        );
    }
}
