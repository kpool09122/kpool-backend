<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\EventHandler;

use DateTimeImmutable;
use Mockery;
use Source\Account\Account\Domain\Event\AccountCategoryChanged;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\EventHandler\AccountCategoryChangedHandler;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountCategoryChangedHandlerTest extends TestCase
{
    public function test__construct(): void
    {
        $this->app->instance(PrincipalGroupFactoryInterface::class, Mockery::mock(PrincipalGroupFactoryInterface::class));
        $this->app->instance(PrincipalGroupRepositoryInterface::class, Mockery::mock(PrincipalGroupRepositoryInterface::class));
        $this->app->instance(PrincipalRepositoryInterface::class, Mockery::mock(PrincipalRepositoryInterface::class));
        $this->app->instance(RoleRepositoryInterface::class, Mockery::mock(RoleRepositoryInterface::class));

        $this->assertInstanceOf(AccountCategoryChangedHandler::class, $this->app->make(AccountCategoryChangedHandler::class));
    }

    public function testHandleCreatesAgencyActorGroupAndAttachesRole(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $reviewerAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->principal($accountIdentifier);
        $principalGroup = $this->principalGroup($accountIdentifier, 'Agency Actor');
        $defaultPrincipalGroup = $this->principalGroup($accountIdentifier, 'Default', true);
        $defaultPrincipalGroup->addMember($principal->principalIdentifier());
        $role = $this->role('AGENCY_ACTOR');
        $event = $this->event($accountIdentifier, AccountCategory::AGENCY, $reviewerAccountIdentifier, AccountType::INDIVIDUAL);

        $principalGroupFactory = self::principalGroupFactoryMock();
        $principalGroupFactory->shouldReceive('create')
            ->once()
            ->with($accountIdentifier, 'Agency Actor', false)
            ->andReturn($principalGroup);

        $principalGroupRepository = self::principalGroupRepositoryMock();
        $principalGroupRepository->shouldReceive('findByAccountIdAndName')
            ->once()
            ->with($accountIdentifier, 'Agency Actor')
            ->andReturnNull();
        $principalGroupRepository->shouldReceive('findDefaultByAccountId')
            ->once()
            ->with($accountIdentifier)
            ->andReturn($defaultPrincipalGroup);
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (PrincipalGroup $saved): bool => $saved->isDefault()
                && ! $saved->hasMember($principal->principalIdentifier())));
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (PrincipalGroup $saved): bool => $saved->hasRole($role->roleIdentifier())
                && $saved->hasMember($principal->principalIdentifier())
                && ! $saved->isDefault()));

        $principalRepository = self::principalRepositoryMock();
        $principalRepository->shouldReceive('findByAccountId')->once()->with($accountIdentifier)->andReturn([$principal]);

        $roleRepository = self::roleRepositoryMock();
        $roleRepository->shouldReceive('findByName')->once()->with('AGENCY_ACTOR')->andReturn($role);

        (new AccountCategoryChangedHandler($principalGroupFactory, $principalGroupRepository, $principalRepository, $roleRepository))
            ->handle($event);
    }

    public function testHandleCreatesTalentActorGroupAndAttachesRole(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $reviewerAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalGroup = $this->principalGroup($accountIdentifier, 'Talent Actor');
        $role = $this->role('TALENT_ACTOR');
        $event = $this->event($accountIdentifier, AccountCategory::TALENT, $reviewerAccountIdentifier, AccountType::INDIVIDUAL);

        $principalGroupFactory = self::principalGroupFactoryMock();
        $principalGroupFactory->shouldReceive('create')
            ->once()
            ->with($accountIdentifier, 'Talent Actor', false)
            ->andReturn($principalGroup);

        $principalGroupRepository = self::principalGroupRepositoryMock();
        $principalGroupRepository->shouldReceive('findByAccountIdAndName')
            ->once()
            ->with($accountIdentifier, 'Talent Actor')
            ->andReturnNull();
        $principalGroupRepository->shouldNotReceive('findDefaultByAccountId');
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (PrincipalGroup $saved): bool => $saved->hasRole($role->roleIdentifier())
                && ! $saved->isDefault()));

        $principalRepository = self::principalRepositoryMock();
        $principalRepository->shouldReceive('findByAccountId')->once()->with($accountIdentifier)->andReturn([]);

        $roleRepository = self::roleRepositoryMock();
        $roleRepository->shouldReceive('findByName')->once()->with('TALENT_ACTOR')->andReturn($role);

        (new AccountCategoryChangedHandler($principalGroupFactory, $principalGroupRepository, $principalRepository, $roleRepository))
            ->handle($event);
    }

    public function testHandleDoesNotAttachMembersForCorporationAccount(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $reviewerAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalGroup = $this->principalGroup($accountIdentifier, 'Agency Actor');
        $role = $this->role('AGENCY_ACTOR');
        $event = $this->event($accountIdentifier, AccountCategory::AGENCY, $reviewerAccountIdentifier, AccountType::CORPORATION);

        $principalGroupFactory = self::principalGroupFactoryMock();
        $principalGroupFactory->shouldReceive('create')
            ->once()
            ->with($accountIdentifier, 'Agency Actor', false)
            ->andReturn($principalGroup);

        $principalGroupRepository = self::principalGroupRepositoryMock();
        $principalGroupRepository->shouldReceive('findByAccountIdAndName')
            ->once()
            ->with($accountIdentifier, 'Agency Actor')
            ->andReturnNull();
        $principalGroupRepository->shouldNotReceive('findDefaultByAccountId');
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (PrincipalGroup $saved): bool => $saved->hasRole($role->roleIdentifier())
                && $saved->memberCount() === 0
                && ! $saved->isDefault()));

        $principalRepository = self::principalRepositoryMock();
        $principalRepository->shouldNotReceive('findByAccountId');

        $roleRepository = self::roleRepositoryMock();
        $roleRepository->shouldReceive('findByName')->once()->with('AGENCY_ACTOR')->andReturn($role);

        (new AccountCategoryChangedHandler($principalGroupFactory, $principalGroupRepository, $principalRepository, $roleRepository))
            ->handle($event);
    }

    public function testHandleDoesNotDuplicateExistingGroupRoleOrMembers(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->principal($accountIdentifier);
        $role = $this->role('AGENCY_ACTOR');
        $existingGroup = $this->principalGroup($accountIdentifier, 'Agency Actor');
        $existingGroup->addRole($role->roleIdentifier());
        $existingGroup->addMember($principal->principalIdentifier());
        $defaultPrincipalGroup = $this->principalGroup($accountIdentifier, 'Default', true);
        $defaultPrincipalGroup->addMember($principal->principalIdentifier());

        $principalGroupFactory = self::principalGroupFactoryMock();
        $principalGroupFactory->shouldNotReceive('create');

        $principalGroupRepository = self::principalGroupRepositoryMock();
        $principalGroupRepository->shouldReceive('findByAccountIdAndName')->once()->andReturn($existingGroup);
        $principalGroupRepository->shouldReceive('findDefaultByAccountId')->once()->with($accountIdentifier)->andReturn($defaultPrincipalGroup);
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (PrincipalGroup $saved): bool => $saved->isDefault()
                && ! $saved->hasMember($principal->principalIdentifier())));
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (PrincipalGroup $saved): bool => count($saved->roles()) === 1 && count($saved->members()) === 1));

        $principalRepository = self::principalRepositoryMock();
        $principalRepository->shouldReceive('findByAccountId')->once()->with($accountIdentifier)->andReturn([$principal]);

        $roleRepository = self::roleRepositoryMock();
        $roleRepository->shouldReceive('findByName')->once()->with('AGENCY_ACTOR')->andReturn($role);

        (new AccountCategoryChangedHandler($principalGroupFactory, $principalGroupRepository, $principalRepository, $roleRepository))
            ->handle($this->event($accountIdentifier, AccountCategory::AGENCY, new AccountIdentifier(StrTestHelper::generateUuid()), AccountType::INDIVIDUAL));
    }

    public function testHandleCreatesGroupWhenWikiIsNotCreated(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $role = $this->role('AGENCY_ACTOR');
        $principalGroup = $this->principalGroup($accountIdentifier, 'Agency Actor');

        $principalGroupFactory = self::principalGroupFactoryMock();
        $principalGroupFactory->shouldReceive('create')->once()->andReturn($principalGroup);

        $principalGroupRepository = self::principalGroupRepositoryMock();
        $principalGroupRepository->shouldReceive('findByAccountIdAndName')->once()->andReturnNull();
        $principalGroupRepository->shouldNotReceive('findDefaultByAccountId');
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (PrincipalGroup $saved): bool => $saved->hasRole($role->roleIdentifier()) && $saved->memberCount() === 0));

        $principalRepository = self::principalRepositoryMock();
        $principalRepository->shouldReceive('findByAccountId')->once()->with($accountIdentifier)->andReturn([]);

        $roleRepository = self::roleRepositoryMock();
        $roleRepository->shouldReceive('findByName')->once()->with('AGENCY_ACTOR')->andReturn($role);

        (new AccountCategoryChangedHandler($principalGroupFactory, $principalGroupRepository, $principalRepository, $roleRepository))
            ->handle($this->event($accountIdentifier, AccountCategory::AGENCY, new AccountIdentifier(StrTestHelper::generateUuid()), AccountType::INDIVIDUAL));
    }

    public function testHandleDoesNothingForGeneralCategory(): void
    {
        $principalGroupFactory = self::principalGroupFactoryMock();
        $principalGroupFactory->shouldNotReceive('create');
        $principalGroupRepository = self::principalGroupRepositoryMock();
        $principalGroupRepository->shouldNotReceive('findByAccountIdAndName');
        $principalGroupRepository->shouldNotReceive('save');
        $principalRepository = self::principalRepositoryMock();
        $principalRepository->shouldNotReceive('findByAccountId');
        $roleRepository = self::roleRepositoryMock();
        $roleRepository->shouldNotReceive('findByName');

        (new AccountCategoryChangedHandler($principalGroupFactory, $principalGroupRepository, $principalRepository, $roleRepository))
            ->handle($this->event(new AccountIdentifier(StrTestHelper::generateUuid()), AccountCategory::GENERAL, new AccountIdentifier(StrTestHelper::generateUuid()), AccountType::INDIVIDUAL));
    }

    /**
     * @return PrincipalGroupFactoryInterface&Mockery\MockInterface
     */
    private static function principalGroupFactoryMock(): PrincipalGroupFactoryInterface
    {
        /** @var PrincipalGroupFactoryInterface&Mockery\MockInterface $mock */
        $mock = Mockery::mock(PrincipalGroupFactoryInterface::class);

        return $mock;
    }

    /**
     * @return PrincipalGroupRepositoryInterface&Mockery\MockInterface
     */
    private static function principalGroupRepositoryMock(): PrincipalGroupRepositoryInterface
    {
        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $mock */
        $mock = Mockery::mock(PrincipalGroupRepositoryInterface::class);

        return $mock;
    }

    /**
     * @return PrincipalRepositoryInterface&Mockery\MockInterface
     */
    private static function principalRepositoryMock(): PrincipalRepositoryInterface
    {
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $mock */
        $mock = Mockery::mock(PrincipalRepositoryInterface::class);

        return $mock;
    }

    /**
     * @return RoleRepositoryInterface&Mockery\MockInterface
     */
    private static function roleRepositoryMock(): RoleRepositoryInterface
    {
        /** @var RoleRepositoryInterface&Mockery\MockInterface $mock */
        $mock = Mockery::mock(RoleRepositoryInterface::class);

        return $mock;
    }

    private function event(AccountIdentifier $accountIdentifier, AccountCategory $newCategory, AccountIdentifier $reviewerAccountIdentifier, AccountType $accountType): AccountCategoryChanged
    {
        return new AccountCategoryChanged(
            $accountIdentifier,
            AccountCategory::GENERAL,
            $newCategory,
            $reviewerAccountIdentifier,
            new DateTimeImmutable(),
            $accountType,
        );
    }

    private function principal(AccountIdentifier $accountIdentifier): Principal
    {
        return new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
        );
    }

    private function principalGroup(AccountIdentifier $accountIdentifier, string $name, bool $isDefault = false): PrincipalGroup
    {
        return new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            $name,
            $isDefault,
            new DateTimeImmutable(),
        );
    }

    private function role(string $name): Role
    {
        return new Role(
            new RoleIdentifier(StrTestHelper::generateUuid()),
            $name,
            [],
            true,
            new DateTimeImmutable(),
        );
    }
}
