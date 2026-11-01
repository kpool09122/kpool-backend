<?php

declare(strict_types=1);

namespace Tests\Database\Seeders;

use Database\Seeders\SystemPolicySeeder;
use Database\Seeders\SystemRoleSeeder;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\Service\PrincipalWikiScopeResolverInterface;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class WikiRoleCompositionTest extends TestCase
{
    /** @return array<string, array{string, ResourceType}> */
    public static function additionalRoles(): array
    {
        return [
            'senior' => ['SeniorCollaborator', ResourceType::GROUP],
            'agency' => ['AgencyActor', ResourceType::AGENCY],
            'talent' => ['TalentActor', ResourceType::TALENT],
        ];
    }

    #[DataProvider('additionalRoles')]
    public function testCollaboratorSuppliesBasicPermissionsWhileAdditionalRolesKeepTheirScope(string $roleName, ResourceType $managedType): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $roleRepository = $this->app()->make(RoleRepositoryInterface::class);
        $collaboratorRole = $roleRepository->findSystemByName('Collaborator');
        $additionalRole = $roleRepository->findSystemByName($roleName);
        self::assertNotNull($collaboratorRole);
        self::assertNotNull($additionalRole);
        self::assertSame([], array_intersect(
            array_map(strval(...), $collaboratorRole->policies()),
            array_map(strval(...), $additionalRole->policies()),
        ));

        $agencyId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();
        $otherId = StrTestHelper::generateUuid();
        $principalWikiScopeResolver = Mockery::mock(PrincipalWikiScopeResolverInterface::class);
        $principalWikiScopeResolver->shouldReceive('agencyWikiIdentifiers')->andReturn([$agencyId]);
        $principalWikiScopeResolver->shouldReceive('talentWikiIdentifiers')->andReturn([$talentId]);
        $this->app()->instance(PrincipalWikiScopeResolverInterface::class, $principalWikiScopeResolver);
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );
        $baseGroup = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $principal->accountIdentifier(), 'Default', true, new DateTimeImmutable());
        $baseGroup->addMember($principal->principalIdentifier());
        $baseGroup->addRole($collaboratorRole);
        $additionalGroup = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $principal->accountIdentifier(), $roleName, false, new DateTimeImmutable());
        $additionalGroup->addMember($principal->principalIdentifier());
        $additionalGroup->addRole($additionalRole);
        $groups = [$baseGroup, $additionalGroup];
        $policyEvaluator = $this->app()->make(PolicyEvaluatorInterface::class);
        $wiki = new Resource(ResourceType::GROUP, editorId: (string) $principal->principalIdentifier());

        self::assertFalse($policyEvaluator->evaluate($principal, Action::CREATE, $wiki, [$additionalGroup]));
        foreach ([Action::CREATE, Action::EDIT, Action::SUBMIT, Action::DELETE, Action::WITHDRAW] as $action) {
            self::assertTrue($policyEvaluator->evaluate($principal, $action, $wiki, $groups), $action->value);
        }
        self::assertTrue($policyEvaluator->evaluate($principal, Action::CREATE_VIDEO_LINK, new Resource(ResourceType::TALENT), $groups));
        self::assertTrue($policyEvaluator->evaluate($principal, Action::DELETE_VIDEO_LINK, new Resource(ResourceType::TALENT), $groups));
        self::assertTrue($policyEvaluator->evaluate($principal, Action::CREATE_PLAYLIST, new Resource(ResourceType::PLAYLIST), $groups));
        self::assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, new Resource($managedType, agencyId: $agencyId, talentIds: [$talentId]), $groups));
        self::assertSame($roleName === 'SeniorCollaborator', $policyEvaluator->evaluate($principal, Action::APPROVE, new Resource($managedType, agencyId: $otherId, talentIds: [$otherId]), $groups));
        self::assertSame($roleName === 'SeniorCollaborator', $policyEvaluator->evaluate($principal, Action::DELETE, new Resource(ResourceType::GROUP, editorId: $otherId), $groups));
        self::assertFalse($policyEvaluator->evaluate($principal, Action::ROLLBACK, $wiki, $groups));
        if ($roleName === 'TalentActor') {
            $seniorRole = $roleRepository->findSystemByName('SeniorCollaborator');
            self::assertNotNull($seniorRole);
            $additionalGroup->addRole($seniorRole);
            self::assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, new Resource(ResourceType::AGENCY, agencyId: $agencyId), $groups));
        }
    }

    public function testReseedingRemovesLegacyCertificationRequestGrantsAndOnlyActorsCanRequest(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $roleRepository = $this->app()->make(RoleRepositoryInterface::class);
        $policyRepository = $this->app()->make(PolicyRepositoryInterface::class);
        $legacyPolicy = $policyRepository->findSystemByName('GLOBAL_OFFICIAL_CERTIFICATION_REQUEST');
        self::assertNotNull($legacyPolicy);
        foreach (['SeniorCollaborator', 'Operator'] as $name) {
            $role = $roleRepository->findSystemByName($name);
            self::assertNotNull($role);
            self::assertNotContains((string) $legacyPolicy->policyIdentifier(), array_map(strval(...), $role->policies()));
            DB::table('wiki_role_policy_attachments')->insert([
                'role_id' => (string) $role->roleIdentifier(),
                'policy_id' => (string) $legacyPolicy->policyIdentifier(),
            ]);
        }
        $this->seed(SystemRoleSeeder::class);
        $this->seed(SystemRoleSeeder::class);
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );
        $policyEvaluator = $this->app()->make(PolicyEvaluatorInterface::class);
        foreach (['Collaborator', 'SeniorCollaborator', 'Operator', 'AgencyActor', 'TalentActor'] as $name) {
            $role = $roleRepository->findSystemByName($name);
            self::assertNotNull($role);
            self::assertNotContains((string) $legacyPolicy->policyIdentifier(), array_map(strval(...), $role->policies()));
            $group = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $principal->accountIdentifier(), $name, false, new DateTimeImmutable());
            $group->addMember($principal->principalIdentifier());
            $group->addRole($role);
            self::assertSame($name === 'AgencyActor', $policyEvaluator->evaluate(
                $principal,
                Action::OFFICIAL_CERTIFICATION_REQUEST,
                new Resource(ResourceType::AGENCY, requesterAccountCategory: AccountCategory::AGENCY),
                [$group],
            ), $name . ' agency request');
            self::assertSame($name === 'TalentActor', $policyEvaluator->evaluate(
                $principal,
                Action::OFFICIAL_CERTIFICATION_REQUEST,
                new Resource(ResourceType::TALENT, requesterAccountCategory: AccountCategory::TALENT),
                [$group],
            ), $name . ' talent request');
        }
    }

    public function testOperatorExplicitlyReceivesSeniorPermissionsAndExclusiveCertificationPermissions(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $roleRepository = $this->app()->make(RoleRepositoryInterface::class);
        $policyRepository = $this->app()->make(PolicyRepositoryInterface::class);
        $operatorRole = $roleRepository->findSystemByName('Operator');
        $seniorRole = $roleRepository->findSystemByName('SeniorCollaborator');
        self::assertNotNull($operatorRole);
        self::assertNotNull($seniorRole);
        $operatorPolicyIds = array_map(strval(...), $operatorRole->policies());
        foreach ($policyRepository->findByIds($seniorRole->policies()) as $policy) {
            foreach ($policy->statements() as $statement) {
                if ($statement->effect() === Effect::ALLOW) {
                    self::assertContains((string) $policy->policyIdentifier(), $operatorPolicyIds, $policy->name());
                } else {
                    self::assertNotContains((string) $policy->policyIdentifier(), $operatorPolicyIds, $policy->name());
                }
            }
        }

        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );
        $operatorGroup = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $principal->accountIdentifier(), 'Operator', false, new DateTimeImmutable());
        $operatorGroup->addMember($principal->principalIdentifier());
        $operatorGroup->addRole($operatorRole);
        $seniorGroup = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $principal->accountIdentifier(), 'Senior Collaborator', false, new DateTimeImmutable());
        $seniorGroup->addMember($principal->principalIdentifier());
        $seniorGroup->addRole($seniorRole);
        $policyEvaluator = $this->app()->make(PolicyEvaluatorInterface::class);
        foreach ([ResourceType::AGENCY, ResourceType::TALENT] as $type) {
            foreach ([Action::OFFICIAL_CERTIFICATION_READ, Action::OFFICIAL_CERTIFICATION_APPROVE, Action::OFFICIAL_CERTIFICATION_REJECT] as $action) {
                self::assertTrue($policyEvaluator->evaluate($principal, $action, new Resource($type), [$operatorGroup]), $action->value);
                self::assertFalse($policyEvaluator->evaluate($principal, $action, new Resource($type), [$seniorGroup]), $action->value);
            }
        }
        $wiki = new Resource(ResourceType::GROUP);
        foreach ([Action::READ, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH, Action::MERGE] as $action) {
            self::assertTrue($policyEvaluator->evaluate($principal, $action, $wiki, [$operatorGroup]), $action->value);
            self::assertTrue($policyEvaluator->evaluate($principal, $action, $wiki, [$seniorGroup]), $action->value);
        }
        self::assertTrue($policyEvaluator->evaluate($principal, Action::CREATE, $wiki, [$operatorGroup]));
        self::assertTrue($policyEvaluator->evaluate($principal, Action::ROLLBACK, $wiki, [$operatorGroup]));
        self::assertFalse($policyEvaluator->evaluate($principal, Action::ROLLBACK, $wiki, [$seniorGroup]));
    }
}
