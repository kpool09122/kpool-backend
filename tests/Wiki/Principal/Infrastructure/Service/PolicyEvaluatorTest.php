<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Service;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Policy;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Condition;
use Source\Wiki\Principal\Domain\ValueObject\ConditionClause;
use Source\Wiki\Principal\Domain\ValueObject\ConditionKey;
use Source\Wiki\Principal\Domain\ValueObject\ConditionOperator;
use Source\Wiki\Principal\Domain\ValueObject\ConditionValue;
use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\Statement;
use Source\Wiki\Principal\Infrastructure\Service\PolicyEvaluator;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreatePrincipalGroup;
use Tests\Helper\CreatePrincipalGroupMembership;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PolicyEvaluatorTest extends TestCase
{
    /** @phpstan-ignore property.uninitialized (setUp で初期化) */
    private PolicyRepositoryInterface $policyRepository;

    /** @phpstan-ignore property.uninitialized (setUp で初期化) */
    private RoleRepositoryInterface $roleRepository;

    /** @phpstan-ignore property.uninitialized (setUp で初期化) */
    private PrincipalGroupRepositoryInterface $principalGroupRepository;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var PolicyRepositoryInterface $policyRepository */
        $policyRepository = $this->app->make(PolicyRepositoryInterface::class);
        $this->policyRepository = $policyRepository;

        /** @var RoleRepositoryInterface $roleRepository */
        $roleRepository = $this->app->make(RoleRepositoryInterface::class);
        $this->roleRepository = $roleRepository;

        /** @var PrincipalGroupRepositoryInterface $principalGroupRepository */
        $principalGroupRepository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $this->principalGroupRepository = $principalGroupRepository;
    }

    /**
     * PrincipalをDBに作成してエンティティを返すヘルパー.
     *
     * @param string[] $groupIds
     * @param string[] $talentIds
     */
    private function createPrincipal(
        ?string $agencyId = null,
        array $groupIds = [],
        array $talentIds = [],
    ): Principal {
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();

        CreateIdentity::create(new IdentityIdentifier($identityId), ['email' => 'test-' . $principalId . '@example.com']);
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );

        return new Principal(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
            $agencyId,
            $groupIds,
            $talentIds
        );
    }

    /**
     * PrincipalGroupを作成して保存し、PrincipalをメンバーとしてDBに追加.
     *
     * @param RoleIdentifier[] $roleIdentifiers
     */
    private function createAndSavePrincipalGroup(
        PrincipalIdentifier $principalIdentifier,
        array $roleIdentifiers = [],
    ): PrincipalGroup {
        $groupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();

        CreateAccount::create($accountId);
        CreatePrincipalGroup::create(
            new PrincipalGroupIdentifier($groupId),
            new AccountIdentifier($accountId),
            ['name' => 'Test Group', 'is_default' => true]
        );
        CreatePrincipalGroupMembership::create($groupId, (string) $principalIdentifier);

        // Roleを追加
        foreach ($roleIdentifiers as $roleIdentifier) {
            \Illuminate\Support\Facades\DB::table('principal_group_role_attachments')->insert([
                'principal_group_id' => $groupId,
                'role_id' => (string) $roleIdentifier,
            ]);
        }

        $group = new PrincipalGroup(
            new PrincipalGroupIdentifier($groupId),
            new AccountIdentifier($accountId),
            'Test Group',
            true,
            new DateTimeImmutable()
        );
        $group->addMember($principalIdentifier);
        foreach ($roleIdentifiers as $roleIdentifier) {
            $group->addRole($roleIdentifier);
        }

        return $group;
    }

    /**
     * Roleを作成して保存するヘルパー.
     *
     * @param PolicyIdentifier[] $policyIdentifiers
     */
    private function createAndSaveRole(
        array $policyIdentifiers = [],
        bool $isSystemRole = false,
    ): Role {
        $role = new Role(
            new RoleIdentifier(StrTestHelper::generateUuid()),
            'Test Role',
            $policyIdentifiers,
            $isSystemRole,
            new DateTimeImmutable()
        );
        $this->roleRepository->save($role);

        return $role;
    }

    /**
     * Policyを作成して保存するヘルパー.
     *
     * @param Statement[] $statements
     */
    private function createAndSavePolicy(
        array $statements,
        bool $isSystemPolicy = false,
    ): Policy {
        $policy = new Policy(
            new PolicyIdentifier(StrTestHelper::generateUuid()),
            'Test Policy',
            $statements,
            $isSystemPolicy,
            new DateTimeImmutable()
        );
        $this->policyRepository->save($policy);

        return $policy;
    }

    /**
     * 正常系: FULL_ACCESS相当のロールを持つPrincipalは全てのアクションが許可される.
     */
    #[Group('useDb')]
    public function testFullAccessRoleAllowsAllActions(): void
    {
        // 全リソース・全アクションを許可するPolicy
        $fullAccessPolicy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                Action::cases(),
                ResourceType::cases(),
                null
            ),
        ]);

        $role = $this->createAndSaveRole([$fullAccessPolicy->policyIdentifier()]);

        $principal = $this->createPrincipal();
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        $resource = new ResourceIdentifier(ResourceType::AGENCY);
        foreach (Action::cases() as $action) {
            $this->assertTrue(
                $policyEvaluator->evaluate($principal, $action, $resource),
                "Action {$action->value} should be allowed for FULL_ACCESS role"
            );
        }
    }

    /**
     * 正常系: ロールを持たないPrincipalは全てのアクションが拒否される.
     */
    #[Group('useDb')]
    public function testNoRoleDeniesAllActions(): void
    {
        $principal = $this->createPrincipal();
        // PrincipalGroupを作成するがRoleはアタッチしない
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            []
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        $resource = new ResourceIdentifier(ResourceType::AGENCY);
        foreach (Action::cases() as $action) {
            $this->assertFalse(
                $policyEvaluator->evaluate($principal, $action, $resource),
                "Action {$action->value} should be denied for principal with no roles"
            );
        }
    }

    /**
     * 正常系: RoleにPolicyがない場合は全てのアクションが拒否される.
     */
    #[Group('useDb')]
    public function testRoleWithoutPoliciesDeniesAllActions(): void
    {
        $role = $this->createAndSaveRole([]);

        $principal = $this->createPrincipal();
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        $resource = new ResourceIdentifier(ResourceType::AGENCY);
        foreach (Action::cases() as $action) {
            $this->assertFalse(
                $policyEvaluator->evaluate($principal, $action, $resource),
                "Action {$action->value} should be denied for role without policies"
            );
        }
    }

    /**
     * 正常系: PrincipalGroupに所属していないPrincipalは全てのアクションが拒否される.
     */
    #[Group('useDb')]
    public function testNoPrincipalGroupDeniesAllActions(): void
    {
        $principal = $this->createPrincipal();
        // PrincipalGroupを作成しない

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        $resource = new ResourceIdentifier(ResourceType::AGENCY);
        foreach (Action::cases() as $action) {
            $this->assertFalse(
                $policyEvaluator->evaluate($principal, $action, $resource),
                "Action {$action->value} should be denied for principal not in any group"
            );
        }
    }

    /**
     * 正常系: DENYがALLOWより優先される.
     */
    #[Group('useDb')]
    public function testDenyTakesPrecedenceOverAllow(): void
    {
        // 全アクションを許可するPolicy
        $allowPolicy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                Action::cases(),
                ResourceType::cases(),
                null
            ),
        ]);

        // ROLLBACKを明示的に拒否するPolicy
        $denyRollbackPolicy = $this->createAndSavePolicy([
            new Statement(
                Effect::DENY,
                [Action::ROLLBACK],
                ResourceType::cases(),
                null
            ),
        ]);

        $role = $this->createAndSaveRole([
            $allowPolicy->policyIdentifier(),
            $denyRollbackPolicy->policyIdentifier(),
        ]);

        $principal = $this->createPrincipal();
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        $resource = new ResourceIdentifier(ResourceType::AGENCY);

        // ROLLBACKは拒否される
        $this->assertFalse(
            $policyEvaluator->evaluate($principal, Action::ROLLBACK, $resource),
            'ROLLBACK should be denied due to explicit DENY'
        );

        // 他のアクションは許可される
        $this->assertTrue(
            $policyEvaluator->evaluate($principal, Action::EDIT, $resource),
            'EDIT should be allowed'
        );
    }

    /**
     * 正常系: 複数のPrincipalGroupに所属している場合、全てのRoleが評価される.
     */
    #[Group('useDb')]
    public function testMultiplePrincipalGroupsAreEvaluated(): void
    {
        // EDITのみ許可するPolicy
        $editPolicy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::EDIT],
                ResourceType::cases(),
                null
            ),
        ]);

        // APPROVEのみ許可するPolicy
        $approvePolicy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::APPROVE],
                ResourceType::cases(),
                null
            ),
        ]);

        $editRole = $this->createAndSaveRole([$editPolicy->policyIdentifier()]);
        $approveRole = $this->createAndSaveRole([$approvePolicy->policyIdentifier()]);

        $principal = $this->createPrincipal();

        // 1つ目のPrincipalGroupを作成
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$editRole->roleIdentifier()]
        );

        // 2つ目のPrincipalGroupを作成
        $groupId2 = StrTestHelper::generateUuid();
        $accountId2 = StrTestHelper::generateUuid();
        CreateAccount::create($accountId2);
        CreatePrincipalGroup::create(
            new PrincipalGroupIdentifier($groupId2),
            new AccountIdentifier($accountId2),
            ['name' => 'Test Group 2', 'is_default' => false]
        );
        CreatePrincipalGroupMembership::create($groupId2, (string) $principal->principalIdentifier());
        \Illuminate\Support\Facades\DB::table('principal_group_role_attachments')->insert([
            'principal_group_id' => $groupId2,
            'role_id' => (string) $approveRole->roleIdentifier(),
        ]);

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        $resource = new ResourceIdentifier(ResourceType::AGENCY);

        // 両方のアクションが許可される
        $this->assertTrue(
            $policyEvaluator->evaluate($principal, Action::EDIT, $resource),
            'EDIT should be allowed from first group'
        );
        $this->assertTrue(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $resource),
            'APPROVE should be allowed from second group'
        );

        // 許可されていないアクションは拒否される
        $this->assertFalse(
            $policyEvaluator->evaluate($principal, Action::ROLLBACK, $resource),
            'ROLLBACK should be denied'
        );
    }

    /**
     * 正常系: Condition評価 - resource:agencyId eq ${principal.agencyId}
     */
    #[Group('useDb')]
    public function testConditionAgencyIdEquals(): void
    {
        $principalAgencyId = StrTestHelper::generateUuid();

        // 自分のAgencyに対してのみ許可するPolicy
        $agencyPolicy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::APPROVE],
                [ResourceType::AGENCY],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_AGENCY_ID,
                        ConditionOperator::EQUALS,
                        ConditionValue::PRINCIPAL_AGENCY_ID
                    ),
                ])
            ),
        ]);

        $role = $this->createAndSaveRole([$agencyPolicy->policyIdentifier()]);

        $principal = $this->createPrincipal($principalAgencyId);
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        // 自分のAgencyへのAPPROVEは許可
        $ownAgency = new ResourceIdentifier(ResourceType::AGENCY, $principalAgencyId);
        $this->assertTrue(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $ownAgency),
            'APPROVE on own agency should be allowed'
        );

        // 他のAgencyへのAPPROVEは拒否
        $otherAgency = new ResourceIdentifier(ResourceType::AGENCY, StrTestHelper::generateUuid());
        $this->assertFalse(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $otherAgency),
            'APPROVE on other agency should be denied'
        );

        // agencyIdがnullのリソースへのAPPROVEは拒否
        $noAgency = new ResourceIdentifier(ResourceType::AGENCY);
        $this->assertFalse(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $noAgency),
            'APPROVE on resource without agencyId should be denied'
        );
    }

    /**
     * 正常系: Condition評価 - resource:groupId eq ${principal.wikiGroupIds}（配列同士の比較）.
     */
    #[Group('useDb')]
    public function testConditionGroupIdsEqualsArrayIntersection(): void
    {
        $groupId1 = StrTestHelper::generateUuid();
        $groupId2 = StrTestHelper::generateUuid();

        $policy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::APPROVE],
                [ResourceType::GROUP],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_GROUP_ID,
                        ConditionOperator::EQUALS,
                        ConditionValue::PRINCIPAL_WIKI_GROUP_IDS
                    ),
                ])
            ),
        ]);

        $role = $this->createAndSaveRole([$policy->policyIdentifier()]);

        $principal = $this->createPrincipal(null, [$groupId1, $groupId2]);
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        // 交差がある場合は許可
        $matchingGroup = new ResourceIdentifier(ResourceType::GROUP, null, [$groupId2]);
        $this->assertTrue(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $matchingGroup),
            'APPROVE should be allowed when groupIds intersect'
        );

        // 交差がない場合は拒否
        $nonMatchingGroup = new ResourceIdentifier(ResourceType::GROUP, null, [StrTestHelper::generateUuid()]);
        $this->assertFalse(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $nonMatchingGroup),
            'APPROVE should be denied when groupIds do not intersect'
        );
    }

    /**
     * 正常系: Condition評価 - resource:agencyId ne ${principal.agencyId}.
     */
    #[Group('useDb')]
    public function testConditionAgencyIdNotEquals(): void
    {
        $principalAgencyId = StrTestHelper::generateUuid();
        $otherAgencyId = StrTestHelper::generateUuid();

        $policy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::APPROVE],
                [ResourceType::AGENCY],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_AGENCY_ID,
                        ConditionOperator::NOT_EQUALS,
                        ConditionValue::PRINCIPAL_AGENCY_ID
                    ),
                ])
            ),
        ]);

        $role = $this->createAndSaveRole([$policy->policyIdentifier()]);

        $principal = $this->createPrincipal($principalAgencyId);
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        $otherAgency = new ResourceIdentifier(ResourceType::AGENCY, $otherAgencyId);
        $this->assertTrue(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $otherAgency),
            'APPROVE should be allowed when agencyId does not match'
        );

        $ownAgency = new ResourceIdentifier(ResourceType::AGENCY, $principalAgencyId);
        $this->assertFalse(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $ownAgency),
            'APPROVE should be denied when agencyId matches'
        );
    }

    /**
     * 正常系: Condition評価 - 型不一致のEQUALSはfalseになる.
     */
    #[Group('useDb')]
    public function testConditionEqualsWithTypeMismatch(): void
    {
        $principalAgencyId = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();

        $policy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::APPROVE],
                [ResourceType::GROUP],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_GROUP_ID,
                        ConditionOperator::EQUALS,
                        ConditionValue::PRINCIPAL_AGENCY_ID
                    ),
                ])
            ),
        ]);

        $role = $this->createAndSaveRole([$policy->policyIdentifier()]);

        $principal = $this->createPrincipal($principalAgencyId, [$groupId]);
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        $resource = new ResourceIdentifier(ResourceType::GROUP, null, [$groupId]);
        $this->assertFalse(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $resource),
            'APPROVE should be denied when condition value type mismatches resource value type'
        );
    }

    /**
     * 正常系: Condition評価 - resource:groupId in ${principal.wikiGroupIds}
     */
    #[Group('useDb')]
    public function testConditionGroupIdIn(): void
    {
        $groupId = StrTestHelper::generateUuid();

        // 自分のGroupに対してのみ許可するPolicy
        $groupPolicy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::APPROVE],
                [ResourceType::GROUP],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_GROUP_ID,
                        ConditionOperator::IN,
                        ConditionValue::PRINCIPAL_WIKI_GROUP_IDS
                    ),
                ])
            ),
        ]);

        $role = $this->createAndSaveRole([$groupPolicy->policyIdentifier()]);

        $principal = $this->createPrincipal(null, [$groupId]);
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        // 自分のGroupへのAPPROVEは許可
        $ownGroup = new ResourceIdentifier(ResourceType::GROUP, null, [$groupId]);
        $this->assertTrue(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $ownGroup),
            'APPROVE on own group should be allowed'
        );

        // 他のGroupへのAPPROVEは拒否
        $otherGroup = new ResourceIdentifier(ResourceType::GROUP, null, [StrTestHelper::generateUuid()]);
        $this->assertFalse(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $otherGroup),
            'APPROVE on other group should be denied'
        );
    }

    /**
     * 正常系: Condition評価 - resource:agencyId in ${principal.agencyId}（スカラー比較）.
     */
    #[Group('useDb')]
    public function testConditionAgencyIdInScalar(): void
    {
        $principalAgencyId = StrTestHelper::generateUuid();

        $policy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::APPROVE],
                [ResourceType::AGENCY],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_AGENCY_ID,
                        ConditionOperator::IN,
                        ConditionValue::PRINCIPAL_AGENCY_ID
                    ),
                ])
            ),
        ]);

        $role = $this->createAndSaveRole([$policy->policyIdentifier()]);

        $principal = $this->createPrincipal($principalAgencyId);
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        $ownAgency = new ResourceIdentifier(ResourceType::AGENCY, $principalAgencyId);
        $this->assertTrue(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $ownAgency),
            'APPROVE should be allowed when agencyId is in principal agencyId'
        );

        $otherAgency = new ResourceIdentifier(ResourceType::AGENCY, StrTestHelper::generateUuid());
        $this->assertFalse(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $otherAgency),
            'APPROVE should be denied when agencyId is not in principal agencyId'
        );
    }

    /**
     * 正常系: Condition評価 - resource:agencyId in ${principal.agencyId} でresourceがnullの場合は拒否.
     */
    #[Group('useDb')]
    public function testConditionAgencyIdInWithNullResource(): void
    {
        $principalAgencyId = StrTestHelper::generateUuid();

        $policy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::APPROVE],
                [ResourceType::AGENCY],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_AGENCY_ID,
                        ConditionOperator::IN,
                        ConditionValue::PRINCIPAL_AGENCY_ID
                    ),
                ])
            ),
        ]);

        $role = $this->createAndSaveRole([$policy->policyIdentifier()]);

        $principal = $this->createPrincipal($principalAgencyId);
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        $resource = new ResourceIdentifier(ResourceType::AGENCY);
        $this->assertFalse(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $resource),
            'APPROVE should be denied when agencyId is null'
        );
    }

    /**
     * 正常系: Condition評価 - principal groupIds が空の場合は IN が false になる.
     */
    #[Group('useDb')]
    public function testConditionGroupIdInWithEmptyPrincipalGroups(): void
    {
        $policy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::APPROVE],
                [ResourceType::GROUP],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_GROUP_ID,
                        ConditionOperator::IN,
                        ConditionValue::PRINCIPAL_WIKI_GROUP_IDS
                    ),
                ])
            ),
        ]);

        $role = $this->createAndSaveRole([$policy->policyIdentifier()]);

        $principal = $this->createPrincipal(null, []);
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        $resource = new ResourceIdentifier(ResourceType::GROUP, null, [StrTestHelper::generateUuid()]);
        $this->assertFalse(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $resource),
            'APPROVE should be denied when principal groupIds are empty'
        );
    }

    /**
     * 正常系: Condition評価 - resource:groupId not_in ${principal.wikiGroupIds}.
     */
    #[Group('useDb')]
    public function testConditionGroupIdNotIn(): void
    {
        $groupId = StrTestHelper::generateUuid();

        $policy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::APPROVE],
                [ResourceType::GROUP],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_GROUP_ID,
                        ConditionOperator::NOT_IN,
                        ConditionValue::PRINCIPAL_WIKI_GROUP_IDS
                    ),
                ])
            ),
        ]);

        $role = $this->createAndSaveRole([$policy->policyIdentifier()]);

        $principal = $this->createPrincipal(null, [$groupId]);
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        $otherGroup = new ResourceIdentifier(ResourceType::GROUP, null, [StrTestHelper::generateUuid()]);
        $this->assertTrue(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $otherGroup),
            'APPROVE should be allowed when groupId is not in principal groups'
        );

        $ownGroup = new ResourceIdentifier(ResourceType::GROUP, null, [$groupId]);
        $this->assertFalse(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $ownGroup),
            'APPROVE should be denied when groupId is in principal groups'
        );
    }

    /**
     * 正常系: Condition評価 - resource:talentId in ${principal.talentIds}
     */
    #[Group('useDb')]
    public function testConditionTalentIdIn(): void
    {
        $talentId = StrTestHelper::generateUuid();

        // 自分のTalentに対してのみ許可するPolicy
        $talentPolicy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::APPROVE],
                [ResourceType::TALENT],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_TALENT_ID,
                        ConditionOperator::IN,
                        ConditionValue::PRINCIPAL_TALENT_IDS
                    ),
                ])
            ),
        ]);

        $role = $this->createAndSaveRole([$talentPolicy->policyIdentifier()]);

        $principal = $this->createPrincipal(null, [], [$talentId]);
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        // 自分のTalentへのAPPROVEは許可
        $ownTalent = new ResourceIdentifier(ResourceType::TALENT, null, [], [$talentId]);
        $this->assertTrue(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $ownTalent),
            'APPROVE on own talent should be allowed'
        );

        // 他のTalentへのAPPROVEは拒否
        $otherTalent = new ResourceIdentifier(ResourceType::TALENT, null, [], [StrTestHelper::generateUuid()]);
        $this->assertFalse(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $otherTalent),
            'APPROVE on other talent should be denied'
        );
    }

    /**
     * 正常系: 複数のConditionClauseはANDで評価される.
     */
    #[Group('useDb')]
    public function testMultipleConditionClausesAreAndEvaluated(): void
    {
        $agencyId = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();

        // agencyId AND groupId の両方が一致する場合のみ許可
        $policy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::APPROVE],
                [ResourceType::GROUP],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_AGENCY_ID,
                        ConditionOperator::EQUALS,
                        ConditionValue::PRINCIPAL_AGENCY_ID
                    ),
                    new ConditionClause(
                        ConditionKey::RESOURCE_GROUP_ID,
                        ConditionOperator::IN,
                        ConditionValue::PRINCIPAL_WIKI_GROUP_IDS
                    ),
                ])
            ),
        ]);

        $role = $this->createAndSaveRole([$policy->policyIdentifier()]);

        $principal = $this->createPrincipal($agencyId, [$groupId]);
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        // 両方一致する場合は許可
        $matchingBoth = new ResourceIdentifier(ResourceType::GROUP, $agencyId, [$groupId]);
        $this->assertTrue(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $matchingBoth),
            'APPROVE should be allowed when both conditions match'
        );

        // agencyIdのみ一致する場合は拒否
        $matchingAgencyOnly = new ResourceIdentifier(ResourceType::GROUP, $agencyId, [StrTestHelper::generateUuid()]);
        $this->assertFalse(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $matchingAgencyOnly),
            'APPROVE should be denied when only agencyId matches'
        );

        // groupIdのみ一致する場合は拒否
        $matchingGroupOnly = new ResourceIdentifier(ResourceType::GROUP, StrTestHelper::generateUuid(), [$groupId]);
        $this->assertFalse(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $matchingGroupOnly),
            'APPROVE should be denied when only groupId matches'
        );
    }

    /**
     * 正常系: リテラル値でのCondition評価（true/false）.
     */
    #[Group('useDb')]
    public function testConditionWithLiteralBooleanValue(): void
    {
        // isOfficial = true の場合のみ許可（リテラル値）
        $policy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::APPROVE],
                [ResourceType::GROUP],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_IS_OFFICIAL,
                        ConditionOperator::EQUALS,
                        true
                    ),
                ])
            ),
        ]);

        $role = $this->createAndSaveRole([$policy->policyIdentifier()]);

        $principal = $this->createPrincipal();
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        // NOTE: ResourceIdentifierにはisOfficialが含まれていないため、このテストは将来の拡張用
        // 現状はConditionを評価しようとしてもisOfficialを取得できないためfalseになる
        $resource = new ResourceIdentifier(ResourceType::GROUP);
        $this->assertFalse(
            $policyEvaluator->evaluate($principal, Action::APPROVE, $resource),
            'APPROVE should be denied when isOfficial condition cannot be evaluated'
        );
    }

    /**
     * 正常系: 基本編集権限のテスト（CREATE, EDIT, SUBMIT のみ許可）.
     */
    #[Group('useDb')]
    public function testBasicEditingPolicy(): void
    {
        // BASIC_EDITING相当のPolicy
        $basicEditingPolicy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::CREATE, Action::EDIT, Action::SUBMIT],
                ResourceType::cases(),
                null
            ),
        ]);

        $role = $this->createAndSaveRole([$basicEditingPolicy->policyIdentifier()]);

        $principal = $this->createPrincipal();
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        $resource = new ResourceIdentifier(ResourceType::GROUP);

        // CREATE, EDIT, SUBMITは許可
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::CREATE, $resource));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::EDIT, $resource));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::SUBMIT, $resource));

        // その他は拒否
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $resource));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::REJECT, $resource));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $resource));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::PUBLISH, $resource));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::MERGE, $resource));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::ROLLBACK, $resource));
    }

    /**
     * 正常系: TALENT_ACTOR相当のテスト - Agencyの承認ができない.
     */
    #[Group('useDb')]
    public function testTalentActorCannotApproveAgency(): void
    {
        $groupId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();

        // BASIC_EDITING
        $basicEditingPolicy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::CREATE, Action::EDIT, Action::SUBMIT],
                ResourceType::cases(),
                null
            ),
        ]);

        // DENY_AGENCY_APPROVAL
        $denyAgencyApprovalPolicy = $this->createAndSavePolicy([
            new Statement(
                Effect::DENY,
                [Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH],
                [ResourceType::AGENCY],
                null
            ),
        ]);

        // TALENT_MANAGEMENT（Group）
        $talentManagementGroupPolicy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::EDIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH, Action::MERGE],
                [ResourceType::GROUP],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_GROUP_ID,
                        ConditionOperator::IN,
                        ConditionValue::PRINCIPAL_WIKI_GROUP_IDS
                    ),
                ])
            ),
        ]);

        // TALENT_MANAGEMENT（Talent）
        $talentManagementTalentPolicy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::EDIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH, Action::MERGE],
                [ResourceType::TALENT],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_TALENT_ID,
                        ConditionOperator::IN,
                        ConditionValue::PRINCIPAL_TALENT_IDS
                    ),
                ])
            ),
        ]);

        $role = $this->createAndSaveRole([
            $basicEditingPolicy->policyIdentifier(),
            $denyAgencyApprovalPolicy->policyIdentifier(),
            $talentManagementGroupPolicy->policyIdentifier(),
            $talentManagementTalentPolicy->policyIdentifier(),
        ]);

        $principal = $this->createPrincipal(null, [$groupId], [$talentId]);
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        $agency = new ResourceIdentifier(ResourceType::AGENCY);

        // Agencyへの承認系アクションは拒否される
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $agency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::REJECT, $agency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $agency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::PUBLISH, $agency));

        // 基本編集は許可される
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::CREATE, $agency));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::EDIT, $agency));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::SUBMIT, $agency));

        // 自分のGroupへの承認は許可される
        $ownGroup = new ResourceIdentifier(ResourceType::GROUP, null, [$groupId]);
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $ownGroup));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::TRANSLATE, $ownGroup));

        // 自分のTalentへの承認は許可される
        $ownTalent = new ResourceIdentifier(ResourceType::TALENT, null, [], [$talentId]);
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $ownTalent));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::TRANSLATE, $ownTalent));
    }

    /**
     * 正常系: AGENCY_ACTOR相当のテスト - 自分のAgencyのみ承認可能.
     */
    #[Group('useDb')]
    public function testAgencyActorScopedToOwnAgency(): void
    {
        $agencyId = StrTestHelper::generateUuid();

        // BASIC_EDITING
        $basicEditingPolicy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::CREATE, Action::EDIT, Action::SUBMIT],
                ResourceType::cases(),
                null
            ),
        ]);

        // AGENCY_MANAGEMENT
        $agencyManagementPolicy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH, Action::MERGE],
                ResourceType::cases(),
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_AGENCY_ID,
                        ConditionOperator::EQUALS,
                        ConditionValue::PRINCIPAL_AGENCY_ID
                    ),
                ])
            ),
        ]);

        $role = $this->createAndSaveRole([
            $basicEditingPolicy->policyIdentifier(),
            $agencyManagementPolicy->policyIdentifier(),
        ]);

        $principal = $this->createPrincipal($agencyId);
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        // 自分のAgencyへの承認は許可される
        $ownAgency = new ResourceIdentifier(ResourceType::AGENCY, $agencyId);
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $ownAgency));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::TRANSLATE, $ownAgency));

        // 他のAgencyへの承認は拒否される
        $otherAgency = new ResourceIdentifier(ResourceType::AGENCY, StrTestHelper::generateUuid());
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $otherAgency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $otherAgency));

        // agencyIdがnullのAgencyへの承認は拒否される
        $noAgency = new ResourceIdentifier(ResourceType::AGENCY);
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $noAgency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $noAgency));

        // Group（agencyIdが一致）への承認は許可される
        $groupInAgency = new ResourceIdentifier(ResourceType::GROUP, $agencyId);
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $groupInAgency));

        // Group（agencyIdが異なる）への承認は拒否される
        $groupOtherAgency = new ResourceIdentifier(ResourceType::GROUP, StrTestHelper::generateUuid());
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $groupOtherAgency));

        // agencyIdがnullのPrincipalは承認できない
        $principalNoAgency = $this->createPrincipal(null);
        $this->createAndSavePrincipalGroup(
            $principalNoAgency->principalIdentifier(),
            [$role->roleIdentifier()]
        );
        $this->assertFalse($policyEvaluator->evaluate($principalNoAgency, Action::APPROVE, $ownAgency));
    }

    /**
     * 正常系: Songリソースに対するGroupまたはTalentの条件評価.
     */
    #[Group('useDb')]
    public function testSongWithGroupOrTalentCondition(): void
    {
        $groupId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();

        // Song承認（自分のGroupの場合）
        $songByGroupPolicy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::APPROVE],
                [ResourceType::SONG],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_GROUP_ID,
                        ConditionOperator::IN,
                        ConditionValue::PRINCIPAL_WIKI_GROUP_IDS
                    ),
                ])
            ),
        ]);

        // Song承認（自分のTalentの場合）
        $songByTalentPolicy = $this->createAndSavePolicy([
            new Statement(
                Effect::ALLOW,
                [Action::APPROVE],
                [ResourceType::SONG],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_TALENT_ID,
                        ConditionOperator::IN,
                        ConditionValue::PRINCIPAL_TALENT_IDS
                    ),
                ])
            ),
        ]);

        $role = $this->createAndSaveRole([
            $songByGroupPolicy->policyIdentifier(),
            $songByTalentPolicy->policyIdentifier(),
        ]);

        $principal = $this->createPrincipal(null, [$groupId], [$talentId]);
        $this->createAndSavePrincipalGroup(
            $principal->principalIdentifier(),
            [$role->roleIdentifier()]
        );

        $policyEvaluator = new PolicyEvaluator(
            $this->principalGroupRepository,
            $this->roleRepository,
            $this->policyRepository
        );

        // Groupが一致するSongへの承認は許可
        $songByGroup = new ResourceIdentifier(ResourceType::SONG, null, [$groupId], []);
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $songByGroup));

        // Talentが一致するSongへの承認は許可
        $songByTalent = new ResourceIdentifier(ResourceType::SONG, null, [], [$talentId]);
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $songByTalent));

        // 両方一致するSongへの承認は許可
        $songByBoth = new ResourceIdentifier(ResourceType::SONG, null, [$groupId], [$talentId]);
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $songByBoth));

        // どちらも一致しないSongへの承認は拒否
        $songByNeither = new ResourceIdentifier(
            ResourceType::SONG,
            null,
            [StrTestHelper::generateUuid()],
            [StrTestHelper::generateUuid()]
        );
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $songByNeither));
    }
}
