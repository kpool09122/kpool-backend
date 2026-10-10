<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator;

use Mockery;
use Mockery\MockInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\SiteManagement\Principal\Application\Exception\AdministratorRoleNotAttachedException;
use Source\SiteManagement\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\SiteManagement\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator\RevokeSiteManagementAdministrator;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator\RevokeSiteManagementAdministratorInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator\RevokeSiteManagementAdministratorInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator\RevokeSiteManagementAdministratorOutput;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\SiteManagementAdministratorTestData;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RevokeSiteManagementAdministratorTest extends TestCase
{
    public function testDeletesTheWholeTargetAccountAdministratorGroupIncludingTheLastAdministrator(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $data->administratorGroup->addRole($data->administratorRole);
        $data->administratorGroup->addMember($data->siteManagementPrincipal->principalIdentifier());
        $this->expectTarget($dependencies, $data, $data->administratorGroup);
        $dependencies->principalGroupRepository->shouldReceive('delete')->once()->with($data->administratorGroup);
        $dependencies->principalRepository->shouldNotReceive('save');

        $this->subject($dependencies)->process(
            new RevokeSiteManagementAdministratorInput($data->email),
            new RevokeSiteManagementAdministratorOutput(),
        );

        $this->assertCount(1, $data->administratorGroup->members());
    }

    public function testDoesNotCheckAccountOperationsMembershipBeforeRevocation(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $data->administratorGroup->addRole($data->administratorRole);
        $data->administratorGroup->addMember($data->siteManagementPrincipal->principalIdentifier());
        $this->expectTarget($dependencies, $data, $data->administratorGroup);
        $dependencies->principalGroupRepository->shouldReceive('delete')->once();

        $this->subject($dependencies)->process(
            new RevokeSiteManagementAdministratorInput($data->email),
            new RevokeSiteManagementAdministratorOutput(),
        );

    }

    public function testScopesGroupLookupToTheTargetAccount(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $data->administratorGroup->addRole($data->administratorRole);
        $data->administratorGroup->addMember($data->siteManagementPrincipal->principalIdentifier());
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturn($data->account);
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()->andReturn($data->administratorRole);
        $dependencies->principalGroupRepository->shouldReceive('findByAccountIdAndName')->once()
            ->with($data->account->accountIdentifier(), 'Operations SiteManagement Administrators')
            ->andReturn($data->administratorGroup);
        $dependencies->principalGroupRepository->shouldReceive('delete')->once()->with($data->administratorGroup);

        $this->subject($dependencies)->process(
            new RevokeSiteManagementAdministratorInput($data->email),
            new RevokeSiteManagementAdministratorOutput(),
        );
    }

    public function testThrowsWhenAccountDoesNotExist(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturnNull();

        $this->expectException(AccountNotFoundException::class);
        $this->subject($dependencies)->process(new RevokeSiteManagementAdministratorInput($data->email), new RevokeSiteManagementAdministratorOutput());
    }

    public function testThrowsWhenAdministratorSystemRoleDoesNotExist(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturn($data->account);
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()->andReturnNull();

        $this->expectException(SystemRoleNotFoundException::class);
        $this->subject($dependencies)->process(new RevokeSiteManagementAdministratorInput($data->email), new RevokeSiteManagementAdministratorOutput());
    }

    public function testThrowsWhenAdministratorGroupDoesNotExistIncludingRepeatedRevocation(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $this->expectTarget($dependencies, $data, null);
        $dependencies->principalGroupRepository->shouldNotReceive('delete');

        $this->expectException(PrincipalGroupNotFoundException::class);
        $this->subject($dependencies)->process(new RevokeSiteManagementAdministratorInput($data->email), new RevokeSiteManagementAdministratorOutput());
    }

    public function testThrowsWhenAdministratorRoleIsNotAttached(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $data->administratorGroup->addMember($data->siteManagementPrincipal->principalIdentifier());
        $this->expectTarget($dependencies, $data, $data->administratorGroup);
        $dependencies->principalGroupRepository->shouldNotReceive('delete');

        $this->expectException(AdministratorRoleNotAttachedException::class);
        $this->subject($dependencies)->process(new RevokeSiteManagementAdministratorInput($data->email), new RevokeSiteManagementAdministratorOutput());
    }

    public function testDeletesGroupAfterOriginalPrincipalWasRemoved(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = SiteManagementAdministratorTestData::create();
        $data->administratorGroup->addRole($data->administratorRole);
        $data->administratorGroup->addMember(new PrincipalIdentifier(StrTestHelper::generateUuid()));
        $this->assertFalse($data->administratorGroup->hasMember($data->siteManagementPrincipal->principalIdentifier()));
        $this->expectTarget($dependencies, $data, $data->administratorGroup);
        $dependencies->principalGroupRepository->shouldReceive('delete')->once()->with($data->administratorGroup);

        $this->subject($dependencies)->process(new RevokeSiteManagementAdministratorInput($data->email), new RevokeSiteManagementAdministratorOutput());
    }

    private function expectTarget(
        RevokeDependencies $dependencies,
        SiteManagementAdministratorTestData $data,
        ?PrincipalGroup $principalGroup,
    ): void {
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->with($data->email)->andReturn($data->account);
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()
            ->with('administrator')->andReturn($data->administratorRole);
        $dependencies->principalGroupRepository->shouldReceive('findByAccountIdAndName')->once()
            ->with($data->account->accountIdentifier(), 'Operations SiteManagement Administrators')
            ->andReturn($principalGroup);
    }

    private function subject(RevokeDependencies $dependencies): RevokeSiteManagementAdministrator
    {
        $this->app()->instance(IdentityRepositoryInterface::class, $dependencies->identityRepository);
        $this->app()->instance(PrincipalRepositoryInterface::class, $dependencies->principalRepository);

        $this->app()->instance(AccountRepositoryInterface::class, $dependencies->accountRepository);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $dependencies->principalGroupRepository);
        $this->app()->instance(RoleRepositoryInterface::class, $dependencies->roleRepository);

        return $this->app()->make(RevokeSiteManagementAdministratorInterface::class);
    }
}

readonly class RevokeDependencies
{
    public function __construct(
        public MockInterface&IdentityRepositoryInterface $identityRepository,
        public MockInterface&AccountRepositoryInterface $accountRepository,
        public MockInterface&PrincipalRepositoryInterface $principalRepository,
        public MockInterface&PrincipalGroupRepositoryInterface $principalGroupRepository,
        public MockInterface&RoleRepositoryInterface $roleRepository,
    ) {
    }

    public static function create(): self
    {
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        /** @var MockInterface&AccountRepositoryInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        /** @var MockInterface&PrincipalRepositoryInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        /** @var MockInterface&PrincipalGroupRepositoryInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        /** @var MockInterface&RoleRepositoryInterface $roleRepository */
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);

        return new self(
            $identityRepository,
            $accountRepository,
            $principalRepository,
            $principalGroupRepository,
            $roleRepository,
        );
    }
}
