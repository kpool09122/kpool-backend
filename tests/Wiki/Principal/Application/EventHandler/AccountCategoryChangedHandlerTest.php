<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\EventHandler;

use DateTimeImmutable;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Account\Account\Domain\Event\AccountCategoryChanged;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;
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
        $this->app()->instance(PrincipalGroupFactoryInterface::class, Mockery::mock(PrincipalGroupFactoryInterface::class));
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, Mockery::mock(PrincipalGroupRepositoryInterface::class));
        $this->app()->instance(PrincipalRepositoryInterface::class, Mockery::mock(PrincipalRepositoryInterface::class));
        $this->app()->instance(RoleRepositoryInterface::class, Mockery::mock(RoleRepositoryInterface::class));

        $this->assertInstanceOf(AccountCategoryChangedHandler::class, $this->app()->make(AccountCategoryChangedHandler::class));
    }

    /** @return array<string, array{AccountType, AccountCategory, string, string}> */
    public static function actorCategories(): array
    {
        return [
            'individual agency' => [AccountType::INDIVIDUAL, AccountCategory::AGENCY, 'AgencyActor', 'Agency Actor'],
            'corporation agency' => [AccountType::CORPORATION, AccountCategory::AGENCY, 'AgencyActor', 'Agency Actor'],
            'individual talent' => [AccountType::INDIVIDUAL, AccountCategory::TALENT, 'TalentActor', 'Talent Actor'],
            'corporation talent' => [AccountType::CORPORATION, AccountCategory::TALENT, 'TalentActor', 'Talent Actor'],
        ];
    }

    #[DataProvider('actorCategories')]
    public function testHandleCreatesActorGroupAndPreservesCollaboratorMembership(AccountType $accountType, AccountCategory $category, string $roleName, string $groupName): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $reviewerAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->principal($accountIdentifier);
        $principalGroup = $this->principalGroup($accountIdentifier, $groupName);
        $defaultPrincipalGroup = $this->principalGroup($accountIdentifier, 'Default', true);
        $defaultPrincipalGroup->addMember($principal->principalIdentifier());
        $nonOwner = $this->principal($accountIdentifier);
        $defaultPrincipalGroup->addMember($nonOwner->principalIdentifier());
        $role = $this->role($roleName);
        $event = $this->event($accountIdentifier, $category, $reviewerAccountIdentifier, $accountType);

        $principalGroupFactory = self::principalGroupFactoryMock();
        $principalGroupFactory->shouldReceive('create')
            ->once()
            ->with($accountIdentifier, $groupName, false)
            ->andReturn($principalGroup);

        $principalGroupRepository = self::principalGroupRepositoryMock();
        $principalGroupRepository->shouldReceive('findByAccountIdAndName')
            ->once()
            ->with($accountIdentifier, $groupName)
            ->andReturnNull();
        $principalGroupRepository->shouldNotReceive('findDefaultByAccountId');
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (PrincipalGroup $saved): bool => $saved->hasRole($role->roleIdentifier())
                && $saved->hasMember($principal->principalIdentifier())
                && ! $saved->hasMember($nonOwner->principalIdentifier())
                && ! $saved->isDefault()));

        $principalRepository = self::principalRepositoryMock();
        $principalRepository->shouldReceive('findAccountOwnersByAccountId')->once()->with($accountIdentifier)->andReturn([$principal]);

        $roleRepository = self::roleRepositoryMock();
        $roleRepository->shouldReceive('findSystemByName')->once()->with($roleName)->andReturn($role);

        (new AccountCategoryChangedHandler($principalGroupFactory, $principalGroupRepository, $principalRepository, $roleRepository))
            ->handle($event);

        $this->assertTrue($defaultPrincipalGroup->hasMember($principal->principalIdentifier()));
        $this->assertTrue($defaultPrincipalGroup->hasMember($nonOwner->principalIdentifier()));
    }

    public function testHandleCreatesTalentActorGroupAndAttachesRole(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $reviewerAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalGroup = $this->principalGroup($accountIdentifier, 'Talent Actor');
        $role = $this->role('TalentActor');
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
        $principalRepository->shouldReceive('findAccountOwnersByAccountId')->once()->with($accountIdentifier)->andReturn([]);

        $roleRepository = self::roleRepositoryMock();
        $roleRepository->shouldReceive('findSystemByName')->once()->with('TalentActor')->andReturn($role);

        (new AccountCategoryChangedHandler($principalGroupFactory, $principalGroupRepository, $principalRepository, $roleRepository))
            ->handle($event);
    }

    public function testHandleDoesNotDuplicateExistingGroupRoleOrMembers(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->principal($accountIdentifier);
        $role = $this->role('AgencyActor');
        $existingGroup = $this->principalGroup($accountIdentifier, 'Agency Actor');
        $existingGroup->addRole($role);
        $existingGroup->addMember($principal->principalIdentifier());
        $defaultPrincipalGroup = $this->principalGroup($accountIdentifier, 'Default', true);
        $defaultPrincipalGroup->addMember($principal->principalIdentifier());

        $principalGroupFactory = self::principalGroupFactoryMock();
        $principalGroupFactory->shouldNotReceive('create');

        $principalGroupRepository = self::principalGroupRepositoryMock();
        $principalGroupRepository->shouldReceive('findByAccountIdAndName')->once()->andReturn($existingGroup);
        $principalGroupRepository->shouldNotReceive('findDefaultByAccountId');
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (PrincipalGroup $saved): bool => count($saved->roles()) === 1 && count($saved->members()) === 1));

        $principalRepository = self::principalRepositoryMock();
        $principalRepository->shouldReceive('findAccountOwnersByAccountId')->once()->with($accountIdentifier)->andReturn([$principal]);

        $roleRepository = self::roleRepositoryMock();
        $roleRepository->shouldReceive('findSystemByName')->once()->with('AgencyActor')->andReturn($role);

        (new AccountCategoryChangedHandler($principalGroupFactory, $principalGroupRepository, $principalRepository, $roleRepository))
            ->handle($this->event($accountIdentifier, AccountCategory::AGENCY, new AccountIdentifier(StrTestHelper::generateUuid()), AccountType::INDIVIDUAL));
    }

    public function testHandleCreatesGroupWhenWikiIsNotCreated(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $role = $this->role('AgencyActor');
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
        $principalRepository->shouldReceive('findAccountOwnersByAccountId')->once()->with($accountIdentifier)->andReturn([]);

        $roleRepository = self::roleRepositoryMock();
        $roleRepository->shouldReceive('findSystemByName')->once()->with('AgencyActor')->andReturn($role);

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
        $principalRepository->shouldNotReceive('findAccountOwnersByAccountId');
        $roleRepository = self::roleRepositoryMock();
        $roleRepository->shouldNotReceive('findSystemByName');

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
            $accountIdentifier,
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
            null,
            new DateTimeImmutable(),
        );
    }
}
