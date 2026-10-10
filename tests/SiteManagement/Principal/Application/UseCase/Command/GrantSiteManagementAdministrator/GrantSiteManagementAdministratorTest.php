<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementAdministrator;

use Mockery;
use Mockery\MockInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException as AccountPrincipalGroupNotFoundException;
use Source\Account\Principal\Application\Exception\PrincipalNotFoundException as AccountPrincipalNotFoundException;
use Source\Account\Principal\Domain\Entity\PrincipalGroup as AccountPrincipalGroup;
use Source\Account\Principal\Domain\Exception\SystemRoleNotFoundException as AccountSystemRoleNotFoundException;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface as AccountPrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface as AccountPrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface as AccountRoleRepositoryInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\SiteManagement\Principal\Application\Exception\OperationsPermissionRequiredException;
use Source\SiteManagement\Principal\Application\Exception\PrincipalNotFoundException;
use Source\SiteManagement\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementAdministrator\GrantSiteManagementAdministrator;
use Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementAdministrator\GrantSiteManagementAdministratorInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementAdministrator\GrantSiteManagementAdministratorOutput;
use Source\SiteManagement\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;
use Tests\Helper\SiteManagementAdministratorTestData;
use Tests\TestCase;

class GrantSiteManagementAdministratorTest extends TestCase
{
    public function testCreatesAccountScopedAdministratorGroupAndGrantsRole(): void
    {
        $dependencies = GrantDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $this->expectOperationsMembership($dependencies, $data);
        $this->expectExistingSiteManagementPrincipal($dependencies, $data);
        $dependencies->principalGroupRepository->shouldReceive('findByAccountIdAndName')->once()
            ->with($data->account->accountIdentifier(), 'Operations SiteManagement Administrators')->andReturnNull();
        $dependencies->principalGroupFactory->shouldReceive('create')->once()
            ->with('Operations SiteManagement Administrators', [], $data->account->accountIdentifier(), false)
            ->andReturn($data->administratorGroup);
        $dependencies->principalGroupRepository->shouldReceive('save')->once()->with($data->administratorGroup);

        $this->subject($dependencies)->process(
            new GrantSiteManagementAdministratorInput($data->email),
            new GrantSiteManagementAdministratorOutput(),
        );

        $this->assertTrue($data->administratorGroup->hasRole($data->administratorRole->roleIdentifier()));
        $this->assertTrue($data->administratorGroup->hasMember($data->siteManagementPrincipal->principalIdentifier()));
        $this->assertSame($data->account->accountIdentifier(), $data->administratorGroup->accountIdentifier());
    }

    public function testUsesExistingGroupWithoutDuplicatingRoleOrMember(): void
    {
        $dependencies = GrantDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $data->administratorGroup->addRole($data->administratorRole);
        $data->administratorGroup->addMember($data->siteManagementPrincipal->principalIdentifier());
        $this->expectOperationsMembership($dependencies, $data);
        $this->expectExistingSiteManagementPrincipal($dependencies, $data);
        $dependencies->principalGroupRepository->shouldReceive('findByAccountIdAndName')->once()
            ->andReturn($data->administratorGroup);
        $dependencies->principalGroupFactory->shouldNotReceive('create');
        $dependencies->principalGroupRepository->shouldReceive('save')->once()->with($data->administratorGroup);

        $this->subject($dependencies)->process(
            new GrantSiteManagementAdministratorInput($data->email),
            new GrantSiteManagementAdministratorOutput(),
        );

        $this->assertCount(1, $data->administratorGroup->roles());
        $this->assertCount(1, $data->administratorGroup->members());
    }

    public function testThrowsWhenSiteManagementPrincipalDoesNotExist(): void
    {
        $dependencies = GrantDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $this->expectOperationsMembership($dependencies, $data);
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()
            ->with('administrator')->andReturn($data->administratorRole);
        $dependencies->principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->with($data->identity->identityIdentifier(), $data->account->accountIdentifier())
            ->andReturnNull();
        $dependencies->principalRepository->shouldNotReceive('save');
        $dependencies->principalGroupFactory->shouldNotReceive('create');
        $dependencies->principalGroupRepository->shouldNotReceive('save');

        $this->expectException(PrincipalNotFoundException::class);
        $this->expectExceptionMessage('Accountと同じメールアドレスのIdentityに紐づくSiteManagement Principalが見つかりません。');
        $this->subject($dependencies)->process(
            new GrantSiteManagementAdministratorInput($data->email),
            new GrantSiteManagementAdministratorOutput(),
        );
    }

    public function testRejectsPrincipalThatOnlyBelongsToAccountWithoutOperationsMembership(): void
    {
        $dependencies = GrantDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $operationsGroup = new AccountPrincipalGroup(
            $data->operationsGroup->principalGroupIdentifier(),
            $data->account->accountIdentifier(),
            'Operations',
            false,
            $data->operationsGroup->createdAt(),
            [$data->operationsRole->roleIdentifier()],
        );
        $dependencies->identityRepository->shouldReceive('findByEmail')->once()->andReturn($data->identity);
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturn($data->account);
        $dependencies->accountPrincipalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->andReturn($data->accountPrincipal);
        $dependencies->accountRoleRepository->shouldReceive('findSystemByName')->once()->andReturn($data->operationsRole);
        $dependencies->accountPrincipalGroupRepository->shouldReceive('findByAccountIdAndRole')->once()
            ->andReturn($operationsGroup);
        $dependencies->principalGroupRepository->shouldNotReceive('save');

        $this->expectException(OperationsPermissionRequiredException::class);
        $this->subject($dependencies)->process(
            new GrantSiteManagementAdministratorInput($data->email),
            new GrantSiteManagementAdministratorOutput(),
        );
    }

    public function testThrowsWhenIdentityDoesNotExist(): void
    {
        $dependencies = GrantDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $dependencies->identityRepository->shouldReceive('findByEmail')->once()->with($data->email)->andReturnNull();

        $this->expectException(IdentityNotFoundException::class);
        $this->subject($dependencies)->process(new GrantSiteManagementAdministratorInput($data->email), new GrantSiteManagementAdministratorOutput());
    }

    public function testThrowsWhenAccountDoesNotExist(): void
    {
        $dependencies = GrantDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $dependencies->identityRepository->shouldReceive('findByEmail')->once()->andReturn($data->identity);
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturnNull();

        $this->expectException(AccountNotFoundException::class);
        $this->subject($dependencies)->process(new GrantSiteManagementAdministratorInput($data->email), new GrantSiteManagementAdministratorOutput());
    }

    public function testThrowsWhenAccountPrincipalDoesNotExist(): void
    {
        $dependencies = GrantDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $dependencies->identityRepository->shouldReceive('findByEmail')->once()->andReturn($data->identity);
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturn($data->account);
        $dependencies->accountPrincipalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->andReturnNull();

        $this->expectException(AccountPrincipalNotFoundException::class);
        $this->subject($dependencies)->process(new GrantSiteManagementAdministratorInput($data->email), new GrantSiteManagementAdministratorOutput());
    }

    public function testThrowsWhenAdministratorSystemRoleDoesNotExist(): void
    {
        $dependencies = GrantDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $this->expectOperationsMembership($dependencies, $data);
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()->with('administrator')->andReturnNull();

        $this->expectException(SystemRoleNotFoundException::class);
        $this->subject($dependencies)->process(new GrantSiteManagementAdministratorInput($data->email), new GrantSiteManagementAdministratorOutput());
    }

    public function testThrowsWhenOperationsSystemRoleDoesNotExist(): void
    {
        $dependencies = GrantDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $dependencies->identityRepository->shouldReceive('findByEmail')->once()->andReturn($data->identity);
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturn($data->account);
        $dependencies->accountPrincipalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->andReturn($data->accountPrincipal);
        $dependencies->accountRoleRepository->shouldReceive('findSystemByName')->once()->andReturnNull();

        $this->expectException(AccountSystemRoleNotFoundException::class);
        $this->subject($dependencies)->process(new GrantSiteManagementAdministratorInput($data->email), new GrantSiteManagementAdministratorOutput());
    }

    public function testThrowsWhenOperationsGroupDoesNotExist(): void
    {
        $dependencies = GrantDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $dependencies->identityRepository->shouldReceive('findByEmail')->once()->andReturn($data->identity);
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturn($data->account);
        $dependencies->accountPrincipalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->andReturn($data->accountPrincipal);
        $dependencies->accountRoleRepository->shouldReceive('findSystemByName')->once()->andReturn($data->operationsRole);
        $dependencies->accountPrincipalGroupRepository->shouldReceive('findByAccountIdAndRole')->once()->andReturnNull();

        $this->expectException(AccountPrincipalGroupNotFoundException::class);
        $this->subject($dependencies)->process(new GrantSiteManagementAdministratorInput($data->email), new GrantSiteManagementAdministratorOutput());
    }

    private function expectOperationsMembership(GrantDependencies $dependencies, SiteManagementAdministratorTestData $data): void
    {
        $dependencies->identityRepository->shouldReceive('findByEmail')->once()->with($data->email)->andReturn($data->identity);
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->with($data->email)->andReturn($data->account);
        $dependencies->accountPrincipalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->with($data->identity->identityIdentifier(), $data->account->accountIdentifier())
            ->andReturn($data->accountPrincipal);
        $dependencies->accountRoleRepository->shouldReceive('findSystemByName')->once()
            ->with('Operations')->andReturn($data->operationsRole);
        $dependencies->accountPrincipalGroupRepository->shouldReceive('findByAccountIdAndRole')->once()
            ->with($data->account->accountIdentifier(), $data->operationsRole->roleIdentifier())
            ->andReturn($data->operationsGroup);
    }

    private function expectExistingSiteManagementPrincipal(GrantDependencies $dependencies, SiteManagementAdministratorTestData $data): void
    {
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()
            ->with('administrator')->andReturn($data->administratorRole);
        $dependencies->principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->with($data->identity->identityIdentifier(), $data->account->accountIdentifier())
            ->andReturn($data->siteManagementPrincipal);
    }

    private function subject(GrantDependencies $dependencies): GrantSiteManagementAdministrator
    {
        return new GrantSiteManagementAdministrator(
            $dependencies->identityRepository,
            $dependencies->accountRepository,
            $dependencies->accountPrincipalRepository,
            $dependencies->accountPrincipalGroupRepository,
            $dependencies->accountRoleRepository,
            $dependencies->principalRepository,
            $dependencies->principalGroupRepository,
            $dependencies->principalGroupFactory,
            $dependencies->roleRepository,
        );
    }
}

readonly class GrantDependencies
{
    public function __construct(
        public MockInterface&IdentityRepositoryInterface $identityRepository,
        public MockInterface&AccountRepositoryInterface $accountRepository,
        public MockInterface&AccountPrincipalRepositoryInterface $accountPrincipalRepository,
        public MockInterface&AccountPrincipalGroupRepositoryInterface $accountPrincipalGroupRepository,
        public MockInterface&AccountRoleRepositoryInterface $accountRoleRepository,
        public MockInterface&PrincipalRepositoryInterface $principalRepository,
        public MockInterface&PrincipalGroupRepositoryInterface $principalGroupRepository,
        public MockInterface&PrincipalGroupFactoryInterface $principalGroupFactory,
        public MockInterface&RoleRepositoryInterface $roleRepository,
    ) {
    }

    public static function create(): self
    {
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        /** @var MockInterface&AccountRepositoryInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        /** @var MockInterface&AccountPrincipalRepositoryInterface $accountPrincipalRepository */
        $accountPrincipalRepository = Mockery::mock(AccountPrincipalRepositoryInterface::class);
        /** @var MockInterface&AccountPrincipalGroupRepositoryInterface $accountPrincipalGroupRepository */
        $accountPrincipalGroupRepository = Mockery::mock(AccountPrincipalGroupRepositoryInterface::class);
        /** @var MockInterface&AccountRoleRepositoryInterface $accountRoleRepository */
        $accountRoleRepository = Mockery::mock(AccountRoleRepositoryInterface::class);
        /** @var MockInterface&PrincipalRepositoryInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        /** @var MockInterface&PrincipalGroupRepositoryInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        /** @var MockInterface&PrincipalGroupFactoryInterface $principalGroupFactory */
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        /** @var MockInterface&RoleRepositoryInterface $roleRepository */
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);

        return new self(
            $identityRepository,
            $accountRepository,
            $accountPrincipalRepository,
            $accountPrincipalGroupRepository,
            $accountRoleRepository,
            $principalRepository,
            $principalGroupRepository,
            $principalGroupFactory,
            $roleRepository,
        );
    }
}
