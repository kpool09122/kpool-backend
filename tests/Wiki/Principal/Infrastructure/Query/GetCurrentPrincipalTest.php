<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Query;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use JsonException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInput;
use Source\Wiki\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInterface;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePolicy;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreatePrincipalGroup;
use Tests\Helper\CreatePrincipalGroupMembership;
use Tests\Helper\CreateRole;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetCurrentPrincipalTest extends TestCase
{
    /**
     * @throws PrincipalNotFoundException
     * @throws BindingResolutionException
     * @throws JsonException
     */
    #[Group('useDb')]
    public function testProcessReturnsCurrentPrincipal(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier, $identityIdentifier, $accountIdentifier);

        $useCase = $this->app->make(GetCurrentPrincipalInterface::class);
        $readModel = $useCase->process(new GetCurrentPrincipalInput($identityIdentifier, $accountIdentifier));

        $result = $readModel->toArray();
        $this->assertSame((string) $principalIdentifier, $result['principalIdentifier']);
        $this->assertSame((string) $identityIdentifier, $result['identityIdentifier']);
        $this->assertFalse($result['isDelegatedPrincipal']);
        $this->assertTrue($result['isEnabled']);
        $this->assertSame([], $result['policies']);
    }

    /**
     * @throws BindingResolutionException
     * @throws JsonException
     */
    #[Group('useDb')]
    public function testProcessReturnsEffectivePoliciesThroughPrincipalGroupAndRole(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier, $identityIdentifier, $accountIdentifier);

        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        CreatePolicy::create($policyIdentifier, [
            'name' => 'Wiki Editor',
            'is_system_policy' => true,
            'statements' => [
                [
                    'effect' => 'allow',
                    'actions' => ['edit'],
                    'resource_types' => ['group'],
                    'condition' => [
                        [
                            'key' => 'resource:groupId',
                            'operator' => 'in',
                            'value' => '${principal.wikiGroupIds}',
                        ],
                    ],
                ],
                [
                    'effect' => 'deny',
                    'actions' => ['rollback'],
                    'resource_types' => ['group'],
                    'condition' => null,
                ],
            ],
        ]);

        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        CreateRole::create($roleIdentifier, ['policies' => [(string) $policyIdentifier]]);

        $principalGroupIdentifier = $this->createPrincipalGroupWithMember($principalIdentifier, $accountIdentifier);
        $this->attachRoleToPrincipalGroup($principalGroupIdentifier, $roleIdentifier);

        $useCase = $this->app->make(GetCurrentPrincipalInterface::class);
        $readModel = $useCase->process(new GetCurrentPrincipalInput($identityIdentifier, $accountIdentifier));

        $result = $readModel->toArray();
        $this->assertSame([
            [
                'policyIdentifier' => (string) $policyIdentifier,
                'name' => 'Wiki Editor',
                'isSystemPolicy' => true,
                'statements' => [
                    [
                        'effect' => 'allow',
                        'actions' => ['edit'],
                        'resourceTypes' => ['group'],
                        'condition' => [
                            'clauses' => [
                                [
                                    'field' => 'resource:groupId',
                                    'operator' => 'in',
                                    'value' => '${principal.wikiGroupIds}',
                                ],
                            ],
                        ],
                    ],
                    [
                        'effect' => 'deny',
                        'actions' => ['rollback'],
                        'resourceTypes' => ['group'],
                        'condition' => null,
                    ],
                ],
            ],
        ], $result['policies']);
    }

    /**
     * @throws BindingResolutionException
     * @throws JsonException
     */
    #[Group('useDb')]
    public function testProcessDoesNotDuplicatePolicyAttachedThroughMultipleRoles(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier, $identityIdentifier, $accountIdentifier);

        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        CreatePolicy::create($policyIdentifier);

        $roleIdentifier1 = new RoleIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier2 = new RoleIdentifier(StrTestHelper::generateUuid());
        CreateRole::create($roleIdentifier1, ['policies' => [(string) $policyIdentifier]]);
        CreateRole::create($roleIdentifier2, ['policies' => [(string) $policyIdentifier]]);

        $principalGroupIdentifier = $this->createPrincipalGroupWithMember($principalIdentifier, $accountIdentifier);
        $this->attachRoleToPrincipalGroup($principalGroupIdentifier, $roleIdentifier1);
        $this->attachRoleToPrincipalGroup($principalGroupIdentifier, $roleIdentifier2);

        $useCase = $this->app->make(GetCurrentPrincipalInterface::class);
        $readModel = $useCase->process(new GetCurrentPrincipalInput($identityIdentifier, $accountIdentifier));

        $result = $readModel->toArray();
        $this->assertCount(1, $result['policies']);
        $this->assertSame((string) $policyIdentifier, $result['policies'][0]['policyIdentifier']);
    }

    /**
     * @throws BindingResolutionException
     * @throws JsonException
     */
    #[Group('useDb')]
    public function testProcessReturnsEmptyPoliciesWhenPrincipalHasNoPolicies(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier, $identityIdentifier, $accountIdentifier);

        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        CreateRole::create($roleIdentifier);

        $principalGroupIdentifier = $this->createPrincipalGroupWithMember($principalIdentifier, $accountIdentifier);
        $this->attachRoleToPrincipalGroup($principalGroupIdentifier, $roleIdentifier);

        $useCase = $this->app->make(GetCurrentPrincipalInterface::class);
        $readModel = $useCase->process(new GetCurrentPrincipalInput($identityIdentifier, $accountIdentifier));

        $result = $readModel->toArray();
        $this->assertSame([], $result['policies']);
    }

    /**
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testProcessThrowsExceptionWhenPrincipalDoesNotExist(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $this->expectException(PrincipalNotFoundException::class);

        $useCase = $this->app->make(GetCurrentPrincipalInterface::class);
        $useCase->process(new GetCurrentPrincipalInput($identityIdentifier, $accountIdentifier));
    }

    #[Group('useDb')]
    public function testProcessUsesIdentityAndAccountPair(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $otherAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);
        CreateAccount::create((string) $otherAccountIdentifier);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $otherPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier, $identityIdentifier, $accountIdentifier);
        CreatePrincipal::create($otherPrincipalIdentifier, $identityIdentifier, $otherAccountIdentifier);

        $useCase = $this->app->make(GetCurrentPrincipalInterface::class);
        $readModel = $useCase->process(new GetCurrentPrincipalInput($identityIdentifier, $otherAccountIdentifier));

        $this->assertSame((string) $otherPrincipalIdentifier, $readModel->toArray()['principalIdentifier']);
    }

    private function createPrincipalGroupWithMember(PrincipalIdentifier $principalIdentifier, AccountIdentifier $accountIdentifier): PrincipalGroupIdentifier
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        CreatePrincipalGroup::create($principalGroupIdentifier, $accountIdentifier);
        CreatePrincipalGroupMembership::create((string) $principalGroupIdentifier, (string) $principalIdentifier);

        return $principalGroupIdentifier;
    }

    private function attachRoleToPrincipalGroup(
        PrincipalGroupIdentifier $principalGroupIdentifier,
        RoleIdentifier $roleIdentifier
    ): void {
        DB::table('wiki_principal_group_role_attachments')->insert([
            'principal_group_id' => (string) $principalGroupIdentifier,
            'role_id' => (string) $roleIdentifier,
        ]);
    }
}
