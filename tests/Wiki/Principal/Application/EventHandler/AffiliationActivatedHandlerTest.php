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
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
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
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);

        $this->app->instance(AffiliationGrantRepositoryInterface::class, $affiliationGrantRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PolicyFactoryInterface::class, $policyFactory);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $this->app->instance(RoleFactoryInterface::class, $roleFactory);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(AffiliationGrantFactoryInterface::class, $affiliationGrantFactory);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

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

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository
            ->shouldReceive('findByOwnerAccountId')
            ->with($talentAccountIdentifier, ResourceType::TALENT)
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
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

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

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository
            ->shouldReceive('findByOwnerAccountId')
            ->with($talentAccountIdentifier, ResourceType::TALENT)
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
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

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

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldNotReceive('findByOwnerAccountId');

        $this->app->instance(AffiliationGrantRepositoryInterface::class, $affiliationGrantRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PolicyFactoryInterface::class, $policyFactory);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $this->app->instance(RoleFactoryInterface::class, $roleFactory);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(AffiliationGrantFactoryInterface::class, $affiliationGrantFactory);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

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
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());

        $event = new AffiliationActivated(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            new DateTimeImmutable(),
        );

        // Talent側のPrincipal
        $talentPrincipal = $this->createPrincipal($talentAccountIdentifier);

        // 公式Talent（Wiki）
        $officialWiki = $this->createWiki($wikiIdentifier, $talentAccountIdentifier);

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

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository
            ->shouldReceive('findByOwnerAccountId')
            ->with($talentAccountIdentifier, ResourceType::TALENT)
            ->once()
            ->andReturn($officialWiki);

        $this->app->instance(AffiliationGrantRepositoryInterface::class, $affiliationGrantRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PolicyFactoryInterface::class, $policyFactory);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $this->app->instance(RoleFactoryInterface::class, $roleFactory);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(AffiliationGrantFactoryInterface::class, $affiliationGrantFactory);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

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

    private function createWiki(
        WikiIdentifier $wikiIdentifier,
        AccountIdentifier $ownerAccountIdentifier,
    ): Wiki {
        $basic = Mockery::mock(BasicInterface::class);

        return new Wiki(
            $wikiIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('tl-chaeyoung'),
            Language::KOREAN,
            ResourceType::TALENT,
            $basic, /** @phpstan-ignore argument.type */
            new SectionContentCollection(),
            null,
            new Version(1),
            $ownerAccountIdentifier,
        );
    }
}
