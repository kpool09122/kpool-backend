<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\EventHandler;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Affiliation\Domain\Event\AffiliationTerminated;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\EventHandler\AffiliationTerminatedHandler;
use Source\Wiki\Principal\Domain\Entity\AffiliationGrant;
use Source\Wiki\Principal\Domain\Entity\Policy;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Repository\AffiliationGrantRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantType;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AffiliationTerminatedHandlerTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $affiliationGrantRepository = Mockery::mock(AffiliationGrantRepositoryInterface::class);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);

        $this->app->instance(AffiliationGrantRepositoryInterface::class, $affiliationGrantRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);

        $handler = $this->app->make(AffiliationTerminatedHandler::class);

        $this->assertInstanceOf(AffiliationTerminatedHandler::class, $handler);
    }

    /**
     * 正常系: 両側のGrant関連リソースが削除されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHandleDeletesAllRelatedResources(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $event = $this->createEvent($affiliationIdentifier, $agencyAccountIdentifier, $talentAccountIdentifier);

        $talentSideGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE);
        $agencySideGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::AGENCY_SIDE);

        $talentSidePrincipalGroup = $this->createPrincipalGroup($talentAccountIdentifier, false);
        $agencySidePrincipalGroup = $this->createPrincipalGroup($agencyAccountIdentifier, false);

        $talentDefaultPrincipalGroup = $this->createPrincipalGroup($talentAccountIdentifier, true);
        $agencyDefaultPrincipalGroup = $this->createPrincipalGroup($agencyAccountIdentifier, true);

        $talentSidePolicy = $this->createPolicy($talentSideGrant->policyIdentifier());
        $agencySidePolicy = $this->createPolicy($agencySideGrant->policyIdentifier());

        $talentSideRole = $this->createRole($talentSideGrant->roleIdentifier());
        $agencySideRole = $this->createRole($agencySideGrant->roleIdentifier());

        $affiliationGrantRepository = Mockery::mock(AffiliationGrantRepositoryInterface::class);
        $affiliationGrantRepository
            ->shouldReceive('findByAffiliationId')
            ->with($affiliationIdentifier)
            ->once()
            ->andReturn([$talentSideGrant, $agencySideGrant]);
        $affiliationGrantRepository
            ->shouldReceive('delete')
            ->twice();

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository
            ->shouldReceive('findById')
            ->with($talentSideGrant->roleIdentifier())
            ->once()
            ->andReturn($talentSideRole);
        $roleRepository
            ->shouldReceive('findById')
            ->with($agencySideGrant->roleIdentifier())
            ->once()
            ->andReturn($agencySideRole);
        $roleRepository
            ->shouldReceive('delete')
            ->twice();

        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository
            ->shouldReceive('findById')
            ->with($talentSideGrant->policyIdentifier())
            ->once()
            ->andReturn($talentSidePolicy);
        $policyRepository
            ->shouldReceive('findById')
            ->with($agencySideGrant->policyIdentifier())
            ->once()
            ->andReturn($agencySidePolicy);
        $policyRepository
            ->shouldReceive('delete')
            ->twice();

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository
            ->shouldReceive('findById')
            ->with($talentSideGrant->principalGroupIdentifier())
            ->once()
            ->andReturn($talentSidePrincipalGroup);
        $principalGroupRepository
            ->shouldReceive('findById')
            ->with($agencySideGrant->principalGroupIdentifier())
            ->once()
            ->andReturn($agencySidePrincipalGroup);
        $principalGroupRepository
            ->shouldReceive('findDefaultByAccountId')
            ->with($talentAccountIdentifier)
            ->once()
            ->andReturn($talentDefaultPrincipalGroup);
        $principalGroupRepository
            ->shouldReceive('findDefaultByAccountId')
            ->with($agencyAccountIdentifier)
            ->once()
            ->andReturn($agencyDefaultPrincipalGroup);
        $principalGroupRepository
            ->shouldReceive('save')
            ->twice();
        $principalGroupRepository
            ->shouldReceive('delete')
            ->twice();

        $this->app->instance(AffiliationGrantRepositoryInterface::class, $affiliationGrantRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);

        $handler = $this->app->make(AffiliationTerminatedHandler::class);

        $handler->handle($event);
    }

    /**
     * 正常系: Affiliation専用グループのPrincipalがDefaultグループに戻ること.
     */
    public function testHandleRestoresAffiliationGroupMembersToDefaultGroup(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $grant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE);
        $affiliationGroup = $this->createPrincipalGroup($accountIdentifier, false, 'Affiliation - Agency 1');
        $affiliationGroup->addMember($principalIdentifier);
        $affiliationGroup->addRole($grant->roleIdentifier());
        $defaultGroup = $this->createPrincipalGroup($accountIdentifier, true, 'Default');

        $principalGroupRepository = $this->mockPrincipalGroupRepositoryForSingleGrant($grant, $affiliationGroup, $defaultGroup);
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (PrincipalGroup $saved): bool => $saved->isDefault()
                && $saved->hasMember($principalIdentifier)));
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (PrincipalGroup $saved): bool => ! $saved->isDefault()
                && ! $saved->hasRole($grant->roleIdentifier())));
        $principalGroupRepository->shouldReceive('delete')->once()->with($affiliationGroup);

        $this->handleSingleGrant($affiliationIdentifier, $grant, $principalGroupRepository);
    }

    /**
     * 正常系: 既にDefaultグループに所属しているPrincipalは重複追加されないこと.
     */
    public function testHandleDoesNotDuplicateMemberAlreadyInDefaultGroup(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $grant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE);
        $affiliationGroup = $this->createPrincipalGroup($accountIdentifier, false, 'Affiliation - Agency 1');
        $affiliationGroup->addMember($principalIdentifier);
        $defaultGroup = $this->createPrincipalGroup($accountIdentifier, true, 'Default');
        $defaultGroup->addMember($principalIdentifier);

        $principalGroupRepository = $this->mockPrincipalGroupRepositoryForSingleGrant($grant, $affiliationGroup, $defaultGroup);
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (PrincipalGroup $saved): bool => ! $saved->isDefault()
                && $saved->memberCount() === 1));
        $principalGroupRepository->shouldReceive('delete')->once()->with($affiliationGroup);

        $this->handleSingleGrant($affiliationIdentifier, $grant, $principalGroupRepository);

        $this->assertSame(1, $defaultGroup->memberCount());
    }

    /**
     * 正常系: Defaultグループがない場合も失敗しないこと.
     */
    public function testHandleDoesNotFailWhenDefaultGroupDoesNotExist(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $grant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE);
        $affiliationGroup = $this->createPrincipalGroup($accountIdentifier, false, 'Affiliation - Agency 1');
        $affiliationGroup->addMember(new PrincipalIdentifier(StrTestHelper::generateUuid()));

        $principalGroupRepository = $this->mockPrincipalGroupRepositoryForSingleGrant($grant, $affiliationGroup, null);
        $principalGroupRepository->shouldReceive('save')->once()->with($affiliationGroup);
        $principalGroupRepository->shouldReceive('delete')->once()->with($affiliationGroup);

        $this->handleSingleGrant($affiliationIdentifier, $grant, $principalGroupRepository);
    }

    /**
     * 正常系: カテゴリ由来のPrincipalGroupやDefaultグループは削除しないこと.
     */
    public function testHandleDoesNotDeleteDefaultOrCategoryPrincipalGroups(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $defaultGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE);
        $actorGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::AGENCY_SIDE);
        $defaultGroup = $this->createPrincipalGroup($accountIdentifier, true, 'Default');
        $actorGroup = $this->createPrincipalGroup($accountIdentifier, false, 'Agency Actor');

        $affiliationGrantRepository = $this->mockAffiliationGrantRepository($affiliationIdentifier, [$defaultGrant, $actorGrant]);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findById')->with($defaultGrant->roleIdentifier())->once()->andReturnNull();
        $roleRepository->shouldReceive('findById')->with($actorGrant->roleIdentifier())->once()->andReturnNull();
        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository->shouldReceive('findById')->with($defaultGrant->policyIdentifier())->once()->andReturnNull();
        $policyRepository->shouldReceive('findById')->with($actorGrant->policyIdentifier())->once()->andReturnNull();
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findById')->with($defaultGrant->principalGroupIdentifier())->once()->andReturn($defaultGroup);
        $principalGroupRepository->shouldReceive('findById')->with($actorGrant->principalGroupIdentifier())->once()->andReturn($actorGroup);
        $principalGroupRepository->shouldNotReceive('findDefaultByAccountId');
        $principalGroupRepository->shouldNotReceive('save');
        $principalGroupRepository->shouldNotReceive('delete');

        $this->app->instance(AffiliationGrantRepositoryInterface::class, $affiliationGrantRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);

        $this->app->make(AffiliationTerminatedHandler::class)->handle(
            $this->createEvent($affiliationIdentifier, $accountIdentifier, $accountIdentifier)
        );
    }

    /**
     * 正常系: system role/policy は削除しないこと.
     */
    public function testHandleDoesNotDeleteSystemRoleOrPolicy(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $grant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE);
        $systemRole = $this->createRole($grant->roleIdentifier(), true);
        $systemPolicy = $this->createPolicy($grant->policyIdentifier(), true);

        $affiliationGrantRepository = $this->mockAffiliationGrantRepository($affiliationIdentifier, [$grant]);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findById')->with($grant->roleIdentifier())->once()->andReturn($systemRole);
        $roleRepository->shouldNotReceive('delete');
        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository->shouldReceive('findById')->with($grant->policyIdentifier())->once()->andReturn($systemPolicy);
        $policyRepository->shouldNotReceive('delete');
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findById')->with($grant->principalGroupIdentifier())->once()->andReturnNull();
        $principalGroupRepository->shouldNotReceive('save');
        $principalGroupRepository->shouldNotReceive('delete');

        $this->app->instance(AffiliationGrantRepositoryInterface::class, $affiliationGrantRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);

        $this->app->make(AffiliationTerminatedHandler::class)->handle(
            $this->createEvent($affiliationIdentifier, $accountIdentifier, $accountIdentifier)
        );
    }

    /**
     * 正常系: Grant が存在しない場合は何もしないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHandleDoesNothingWhenNoGrantsExist(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $event = $this->createEvent($affiliationIdentifier, $agencyAccountIdentifier, $talentAccountIdentifier);

        $affiliationGrantRepository = Mockery::mock(AffiliationGrantRepositoryInterface::class);
        $affiliationGrantRepository
            ->shouldReceive('findByAffiliationId')
            ->with($affiliationIdentifier)
            ->once()
            ->andReturn([]);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldNotReceive('delete');

        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository->shouldNotReceive('delete');

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldNotReceive('delete');

        $this->app->instance(AffiliationGrantRepositoryInterface::class, $affiliationGrantRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);

        $handler = $this->app->make(AffiliationTerminatedHandler::class);

        $handler->handle($event);
    }

    /**
     * 正常系: 既に一部リソースがない場合も冪等に処理できること.
     */
    public function testHandleIsIdempotentWhenSomeResourcesDoNotExist(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $grant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE);

        $affiliationGrantRepository = $this->mockAffiliationGrantRepository($affiliationIdentifier, [$grant]);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findById')->with($grant->roleIdentifier())->once()->andReturnNull();
        $roleRepository->shouldNotReceive('delete');
        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository->shouldReceive('findById')->with($grant->policyIdentifier())->once()->andReturnNull();
        $policyRepository->shouldNotReceive('delete');
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findById')->with($grant->principalGroupIdentifier())->once()->andReturnNull();
        $principalGroupRepository->shouldNotReceive('findDefaultByAccountId');
        $principalGroupRepository->shouldNotReceive('save');
        $principalGroupRepository->shouldNotReceive('delete');

        $this->app->instance(AffiliationGrantRepositoryInterface::class, $affiliationGrantRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);

        $this->app->make(AffiliationTerminatedHandler::class)->handle(
            $this->createEvent($affiliationIdentifier, $accountIdentifier, $accountIdentifier)
        );
    }

    private function handleSingleGrant(
        AffiliationIdentifier $affiliationIdentifier,
        AffiliationGrant $grant,
        PrincipalGroupRepositoryInterface $principalGroupRepository,
    ): void {
        $affiliationGrantRepository = $this->mockAffiliationGrantRepository($affiliationIdentifier, [$grant]);
        $role = $this->createRole($grant->roleIdentifier());
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findById')->once()->with($grant->roleIdentifier())->andReturn($role);
        $roleRepository->shouldReceive('delete')->once()->with($role);
        $policy = $this->createPolicy($grant->policyIdentifier());
        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository->shouldReceive('findById')->once()->with($grant->policyIdentifier())->andReturn($policy);
        $policyRepository->shouldReceive('delete')->once()->with($policy);

        $this->app->instance(AffiliationGrantRepositoryInterface::class, $affiliationGrantRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);

        $this->app->make(AffiliationTerminatedHandler::class)->handle(
            $this->createEvent(
                $affiliationIdentifier,
                new AccountIdentifier(StrTestHelper::generateUuid()),
                new AccountIdentifier(StrTestHelper::generateUuid()),
            )
        );
    }

    /**
     * @param AffiliationGrant[] $grants
     * @return AffiliationGrantRepositoryInterface&Mockery\MockInterface
     */
    private function mockAffiliationGrantRepository(
        AffiliationIdentifier $affiliationIdentifier,
        array $grants,
    ): AffiliationGrantRepositoryInterface {
        /** @var AffiliationGrantRepositoryInterface&Mockery\MockInterface $affiliationGrantRepository */
        $affiliationGrantRepository = Mockery::mock(AffiliationGrantRepositoryInterface::class);
        $affiliationGrantRepository->shouldReceive('findByAffiliationId')
            ->once()
            ->with($affiliationIdentifier)
            ->andReturn($grants);
        $affiliationGrantRepository->shouldReceive('delete')->times(count($grants));

        return $affiliationGrantRepository;
    }

    /**
     * @return PrincipalGroupRepositoryInterface&Mockery\MockInterface
     */
    private function mockPrincipalGroupRepositoryForSingleGrant(
        AffiliationGrant $grant,
        PrincipalGroup $affiliationGroup,
        ?PrincipalGroup $defaultGroup,
    ): PrincipalGroupRepositoryInterface {
        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findById')
            ->once()
            ->with($grant->principalGroupIdentifier())
            ->andReturn($affiliationGroup);
        $principalGroupRepository->shouldReceive('findDefaultByAccountId')
            ->once()
            ->with($affiliationGroup->accountIdentifier())
            ->andReturn($defaultGroup);

        return $principalGroupRepository;
    }

    private function createEvent(
        AffiliationIdentifier $affiliationIdentifier,
        AccountIdentifier $agencyAccountIdentifier,
        AccountIdentifier $talentAccountIdentifier,
    ): AffiliationTerminated {
        return new AffiliationTerminated(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            new DateTimeImmutable(),
        );
    }

    private function createAffiliationGrant(
        AffiliationIdentifier $affiliationIdentifier,
        AffiliationGrantType $type,
    ): AffiliationGrant {
        return new AffiliationGrant(
            new AffiliationGrantIdentifier(StrTestHelper::generateUuid()),
            $affiliationIdentifier,
            new PolicyIdentifier(StrTestHelper::generateUuid()),
            new RoleIdentifier(StrTestHelper::generateUuid()),
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $type,
            new DateTimeImmutable(),
        );
    }

    private function createPrincipalGroup(
        AccountIdentifier $accountIdentifier,
        bool $isDefault,
        string $name = 'Affiliation - Test',
    ): PrincipalGroup {
        return new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            $name,
            $isDefault,
            new DateTimeImmutable(),
        );
    }

    private function createPolicy(PolicyIdentifier $policyIdentifier, bool $isSystemPolicy = false): Policy
    {
        return new Policy(
            $policyIdentifier,
            'Test Policy',
            [],
            $isSystemPolicy,
            new DateTimeImmutable(),
        );
    }

    private function createRole(RoleIdentifier $roleIdentifier, bool $isSystemRole = false): Role
    {
        return new Role(
            $roleIdentifier,
            'Test Role',
            [],
            $isSystemRole,
            new DateTimeImmutable(),
        );
    }
}
