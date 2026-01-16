<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\EventHandler;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Affiliation\Domain\Event\AffiliationActivated;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Application\EventHandler\AffiliationActivatedHandler;
use Source\Wiki\Principal\Domain\Entity\AffiliationGrant;
use Source\Wiki\Principal\Domain\Entity\Policy;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Factory\AffiliationGrantFactoryInterface;
use Source\Wiki\Principal\Domain\Factory\PolicyFactoryInterface;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\Factory\RoleFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\AffiliationGrantRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantType;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AffiliationActivatedHandlerTest extends TestCase
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
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $policyFactory = Mockery::mock(PolicyFactoryInterface::class);
        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $roleFactory = Mockery::mock(RoleFactoryInterface::class);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $affiliationGrantFactory = Mockery::mock(AffiliationGrantFactoryInterface::class);
        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);

        $this->app->instance(AffiliationGrantRepositoryInterface::class, $affiliationGrantRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PolicyFactoryInterface::class, $policyFactory);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $this->app->instance(RoleFactoryInterface::class, $roleFactory);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(AffiliationGrantFactoryInterface::class, $affiliationGrantFactory);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $handler = $this->app->make(AffiliationActivatedHandler::class);

        $this->assertInstanceOf(AffiliationActivatedHandler::class, $handler);
    }

    /**
     * 正常系: Talent側とAgency側の両方のPolicy/Role/PrincipalGroupが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHandleCreatesGrantsForBothSides(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $event = new AffiliationActivated(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            new DateTimeImmutable(),
        );

        // Talent側のPrincipal
        $talentPrincipal = $this->createPrincipal($talentAccountIdentifier);

        // 作成されるエンティティ
        $talentSidePrincipalGroup = $this->createPrincipalGroup($talentAccountIdentifier);
        $talentSidePolicy = $this->createPolicy();
        $talentSideRole = $this->createRole();
        $talentSideGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE);

        $agencySidePrincipalGroup = $this->createPrincipalGroup($agencyAccountIdentifier);
        $agencySidePolicy = $this->createPolicy();
        $agencySideRole = $this->createRole();
        $agencySideGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::AGENCY_SIDE);

        // Mocks
        $affiliationGrantRepository = Mockery::mock(AffiliationGrantRepositoryInterface::class);
        $affiliationGrantRepository
            ->shouldReceive('findByAffiliationIdAndType')
            ->with($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE)
            ->once()
            ->andReturnNull();
        $affiliationGrantRepository
            ->shouldReceive('findByAffiliationIdAndType')
            ->with($affiliationIdentifier, AffiliationGrantType::AGENCY_SIDE)
            ->once()
            ->andReturnNull();
        $affiliationGrantRepository
            ->shouldReceive('save')
            ->twice();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository
            ->shouldReceive('findByAccountId')
            ->with($talentAccountIdentifier)
            ->once()
            ->andReturn([$talentPrincipal]);

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory
            ->shouldReceive('create')
            ->twice()
            ->andReturn($talentSidePrincipalGroup, $agencySidePrincipalGroup);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository
            ->shouldReceive('save')
            ->times(5); // Talent側は3回(作成後、メンバー追加後、Role追加後)、Agency側は2回(作成後、Role追加後)

        $policyFactory = Mockery::mock(PolicyFactoryInterface::class);
        $policyFactory
            ->shouldReceive('create')
            ->twice()
            ->andReturn($talentSidePolicy, $agencySidePolicy);

        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository
            ->shouldReceive('save')
            ->twice();

        $roleFactory = Mockery::mock(RoleFactoryInterface::class);
        $roleFactory
            ->shouldReceive('create')
            ->twice()
            ->andReturn($talentSideRole, $agencySideRole);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository
            ->shouldReceive('save')
            ->twice();

        $affiliationGrantFactory = Mockery::mock(AffiliationGrantFactoryInterface::class);
        $affiliationGrantFactory
            ->shouldReceive('create')
            ->twice()
            ->andReturn($talentSideGrant, $agencySideGrant);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository
            ->shouldReceive('findByOwnerAccountId')
            ->with($talentAccountIdentifier)
            ->once()
            ->andReturnNull();

        $this->app->instance(AffiliationGrantRepositoryInterface::class, $affiliationGrantRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PolicyFactoryInterface::class, $policyFactory);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $this->app->instance(RoleFactoryInterface::class, $roleFactory);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(AffiliationGrantFactoryInterface::class, $affiliationGrantFactory);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $handler = $this->app->make(AffiliationActivatedHandler::class);

        $handler->handle($event);
    }

    /**
     * 正常系: 既存のTALENT_SIDE Grantがある場合はスキップされること（冪等性）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHandleSkipsExistingTalentSideGrant(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $event = new AffiliationActivated(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            new DateTimeImmutable(),
        );

        $existingTalentSideGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE);
        $agencySideGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::AGENCY_SIDE);
        $agencySidePrincipalGroup = $this->createPrincipalGroup($agencyAccountIdentifier);
        $agencySidePolicy = $this->createPolicy();
        $agencySideRole = $this->createRole();

        $affiliationGrantRepository = Mockery::mock(AffiliationGrantRepositoryInterface::class);
        $affiliationGrantRepository
            ->shouldReceive('findByAffiliationIdAndType')
            ->with($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE)
            ->once()
            ->andReturn($existingTalentSideGrant);
        $affiliationGrantRepository
            ->shouldReceive('findByAffiliationIdAndType')
            ->with($affiliationIdentifier, AffiliationGrantType::AGENCY_SIDE)
            ->once()
            ->andReturnNull();
        $affiliationGrantRepository
            ->shouldReceive('save')
            ->once();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findByAccountId');

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory
            ->shouldReceive('create')
            ->once()
            ->andReturn($agencySidePrincipalGroup);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository
            ->shouldReceive('save')
            ->twice(); // 作成後、Role追加後

        $policyFactory = Mockery::mock(PolicyFactoryInterface::class);
        $policyFactory
            ->shouldReceive('create')
            ->once()
            ->andReturn($agencySidePolicy);

        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository
            ->shouldReceive('save')
            ->once();

        $roleFactory = Mockery::mock(RoleFactoryInterface::class);
        $roleFactory
            ->shouldReceive('create')
            ->once()
            ->andReturn($agencySideRole);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository
            ->shouldReceive('save')
            ->once();

        $affiliationGrantFactory = Mockery::mock(AffiliationGrantFactoryInterface::class);
        $affiliationGrantFactory
            ->shouldReceive('create')
            ->once()
            ->andReturn($agencySideGrant);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository
            ->shouldReceive('findByOwnerAccountId')
            ->with($talentAccountIdentifier)
            ->once()
            ->andReturnNull();

        $this->app->instance(AffiliationGrantRepositoryInterface::class, $affiliationGrantRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PolicyFactoryInterface::class, $policyFactory);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $this->app->instance(RoleFactoryInterface::class, $roleFactory);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(AffiliationGrantFactoryInterface::class, $affiliationGrantFactory);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $handler = $this->app->make(AffiliationActivatedHandler::class);

        $handler->handle($event);
    }

    /**
     * 正常系: 既存のAGENCY_SIDE Grantがある場合はスキップされること（冪等性）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHandleSkipsExistingAgencySideGrant(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $event = new AffiliationActivated(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            new DateTimeImmutable(),
        );

        // Talent側のPrincipal
        $talentPrincipal = $this->createPrincipal($talentAccountIdentifier);

        // Talent側のリソース
        $talentSidePrincipalGroup = $this->createPrincipalGroup($talentAccountIdentifier);
        $talentSidePolicy = $this->createPolicy();
        $talentSideRole = $this->createRole();
        $talentSideGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE);

        // 既存のAgency側Grant
        $existingAgencySideGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::AGENCY_SIDE);

        $affiliationGrantRepository = Mockery::mock(AffiliationGrantRepositoryInterface::class);
        $affiliationGrantRepository
            ->shouldReceive('findByAffiliationIdAndType')
            ->with($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE)
            ->once()
            ->andReturnNull();
        $affiliationGrantRepository
            ->shouldReceive('findByAffiliationIdAndType')
            ->with($affiliationIdentifier, AffiliationGrantType::AGENCY_SIDE)
            ->once()
            ->andReturn($existingAgencySideGrant);
        $affiliationGrantRepository
            ->shouldReceive('save')
            ->once(); // Talent側のみ

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository
            ->shouldReceive('findByAccountId')
            ->with($talentAccountIdentifier)
            ->once()
            ->andReturn([$talentPrincipal]);

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory
            ->shouldReceive('create')
            ->once()
            ->andReturn($talentSidePrincipalGroup);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository
            ->shouldReceive('save')
            ->times(3); // 作成後、メンバー追加後、Role追加後

        $policyFactory = Mockery::mock(PolicyFactoryInterface::class);
        $policyFactory
            ->shouldReceive('create')
            ->once()
            ->andReturn($talentSidePolicy);

        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository
            ->shouldReceive('save')
            ->once();

        $roleFactory = Mockery::mock(RoleFactoryInterface::class);
        $roleFactory
            ->shouldReceive('create')
            ->once()
            ->andReturn($talentSideRole);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository
            ->shouldReceive('save')
            ->once();

        $affiliationGrantFactory = Mockery::mock(AffiliationGrantFactoryInterface::class);
        $affiliationGrantFactory
            ->shouldReceive('create')
            ->once()
            ->andReturn($talentSideGrant);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldNotReceive('findByOwnerAccountId');

        $this->app->instance(AffiliationGrantRepositoryInterface::class, $affiliationGrantRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PolicyFactoryInterface::class, $policyFactory);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $this->app->instance(RoleFactoryInterface::class, $roleFactory);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(AffiliationGrantFactoryInterface::class, $affiliationGrantFactory);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $handler = $this->app->make(AffiliationActivatedHandler::class);

        $handler->handle($event);
    }

    /**
     * 正常系: 公式Talentが存在する場合、Agency側にTalent制限付きの権限が付与されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHandleCreatesAgencySideGrantWithTalentCondition(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());

        $event = new AffiliationActivated(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            new DateTimeImmutable(),
        );

        // Talent側のPrincipal
        $talentPrincipal = $this->createPrincipal($talentAccountIdentifier);

        // 公式Talent
        $officialTalent = $this->createTalent($talentIdentifier, $talentAccountIdentifier);

        // 作成されるエンティティ
        $talentSidePrincipalGroup = $this->createPrincipalGroup($talentAccountIdentifier);
        $talentSidePolicy = $this->createPolicy();
        $talentSideRole = $this->createRole();
        $talentSideGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE);

        $agencySidePrincipalGroup = $this->createPrincipalGroup($agencyAccountIdentifier);
        $agencySidePolicy = $this->createPolicy();
        $agencySideRole = $this->createRole();
        $agencySideGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::AGENCY_SIDE);

        // Mocks
        $affiliationGrantRepository = Mockery::mock(AffiliationGrantRepositoryInterface::class);
        $affiliationGrantRepository
            ->shouldReceive('findByAffiliationIdAndType')
            ->with($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE)
            ->once()
            ->andReturnNull();
        $affiliationGrantRepository
            ->shouldReceive('findByAffiliationIdAndType')
            ->with($affiliationIdentifier, AffiliationGrantType::AGENCY_SIDE)
            ->once()
            ->andReturnNull();
        $affiliationGrantRepository
            ->shouldReceive('save')
            ->twice();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository
            ->shouldReceive('findByAccountId')
            ->with($talentAccountIdentifier)
            ->once()
            ->andReturn([$talentPrincipal]);

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory
            ->shouldReceive('create')
            ->twice()
            ->andReturn($talentSidePrincipalGroup, $agencySidePrincipalGroup);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository
            ->shouldReceive('save')
            ->times(5);

        $policyFactory = Mockery::mock(PolicyFactoryInterface::class);
        $policyFactory
            ->shouldReceive('create')
            ->twice()
            ->andReturn($talentSidePolicy, $agencySidePolicy);

        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository
            ->shouldReceive('save')
            ->twice();

        $roleFactory = Mockery::mock(RoleFactoryInterface::class);
        $roleFactory
            ->shouldReceive('create')
            ->twice()
            ->andReturn($talentSideRole, $agencySideRole);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository
            ->shouldReceive('save')
            ->twice();

        $affiliationGrantFactory = Mockery::mock(AffiliationGrantFactoryInterface::class);
        $affiliationGrantFactory
            ->shouldReceive('create')
            ->twice()
            ->andReturn($talentSideGrant, $agencySideGrant);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository
            ->shouldReceive('findByOwnerAccountId')
            ->with($talentAccountIdentifier)
            ->once()
            ->andReturn($officialTalent);

        $this->app->instance(AffiliationGrantRepositoryInterface::class, $affiliationGrantRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PolicyFactoryInterface::class, $policyFactory);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $this->app->instance(RoleFactoryInterface::class, $roleFactory);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(AffiliationGrantFactoryInterface::class, $affiliationGrantFactory);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $handler = $this->app->make(AffiliationActivatedHandler::class);

        $handler->handle($event);
    }

    private function createPrincipal(AccountIdentifier $accountIdentifier): Principal
    {
        return new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            (string) $accountIdentifier,
            [],
            [],
            null,
            true,
        );
    }

    private function createPrincipalGroup(AccountIdentifier $accountIdentifier): PrincipalGroup
    {
        return new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Test Group',
            false,
            new DateTimeImmutable(),
        );
    }

    private function createPolicy(): Policy
    {
        return new Policy(
            new PolicyIdentifier(StrTestHelper::generateUuid()),
            'Test Policy',
            [],
            false,
            new DateTimeImmutable(),
        );
    }

    private function createRole(): Role
    {
        return new Role(
            new RoleIdentifier(StrTestHelper::generateUuid()),
            'Test Role',
            [],
            false,
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

    private function createTalent(
        TalentIdentifier $talentIdentifier,
        AccountIdentifier $ownerAccountIdentifier,
    ): Talent {
        return new Talent(
            $talentIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            Language::JAPANESE,
            new TalentName('Test Talent'),
            'test talent',
            new RealName('Test Real Name'),
            'test real name',
            null,
            [],
            null,
            new Career('Test Career'),
            null,
            RelevantVideoLinks::formStringArray([]),
            new Version(1),
            null,
            null,
            true,
            $ownerAccountIdentifier,
        );
    }
}
