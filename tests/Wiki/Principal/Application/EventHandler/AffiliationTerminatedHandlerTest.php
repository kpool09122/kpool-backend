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

        $event = new AffiliationTerminated(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            new DateTimeImmutable(),
        );

        $talentSideGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE);
        $agencySideGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::AGENCY_SIDE);

        $talentSidePrincipalGroup = $this->createPrincipalGroup($talentAccountIdentifier, false);
        $agencySidePrincipalGroup = $this->createPrincipalGroup($agencyAccountIdentifier, false);

        $talentSidePolicy = $this->createPolicy($talentSideGrant->policyIdentifier());
        $agencySidePolicy = $this->createPolicy($agencySideGrant->policyIdentifier());

        $talentSideRole = $this->createRole($talentSideGrant->roleIdentifier());
        $agencySideRole = $this->createRole($agencySideGrant->roleIdentifier());

        // Mocks
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

        $event = new AffiliationTerminated(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            new DateTimeImmutable(),
        );

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
     * 正常系: デフォルトのPrincipalGroupは削除しないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHandleSkipsDefaultPrincipalGroup(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $event = new AffiliationTerminated(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            new DateTimeImmutable(),
        );

        $talentSideGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE);

        // Default PrincipalGroup
        $defaultPrincipalGroup = $this->createPrincipalGroup($talentAccountIdentifier, true);
        $talentSidePolicy = $this->createPolicy($talentSideGrant->policyIdentifier());
        $talentSideRole = $this->createRole($talentSideGrant->roleIdentifier());

        $affiliationGrantRepository = Mockery::mock(AffiliationGrantRepositoryInterface::class);
        $affiliationGrantRepository
            ->shouldReceive('findByAffiliationId')
            ->with($affiliationIdentifier)
            ->once()
            ->andReturn([$talentSideGrant]);
        $affiliationGrantRepository
            ->shouldReceive('delete')
            ->once();

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($talentSideRole);
        $roleRepository
            ->shouldReceive('delete')
            ->once();

        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($talentSidePolicy);
        $policyRepository
            ->shouldReceive('delete')
            ->once();

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($defaultPrincipalGroup);
        $principalGroupRepository->shouldNotReceive('delete'); // Default なので削除されない

        $this->app->instance(AffiliationGrantRepositoryInterface::class, $affiliationGrantRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);

        $handler = $this->app->make(AffiliationTerminatedHandler::class);

        $handler->handle($event);
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

    private function createPrincipalGroup(AccountIdentifier $accountIdentifier, bool $isDefault): PrincipalGroup
    {
        return new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Test Group',
            $isDefault,
            new DateTimeImmutable(),
        );
    }

    private function createPolicy(PolicyIdentifier $policyIdentifier): Policy
    {
        return new Policy(
            $policyIdentifier,
            'Test Policy',
            [],
            false,
            new DateTimeImmutable(),
        );
    }

    private function createRole(RoleIdentifier $roleIdentifier): Role
    {
        return new Role(
            $roleIdentifier,
            'Test Role',
            [],
            false,
            new DateTimeImmutable(),
        );
    }
}
