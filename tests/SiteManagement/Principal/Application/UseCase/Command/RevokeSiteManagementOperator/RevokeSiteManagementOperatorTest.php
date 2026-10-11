<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator;

use Mockery;
use Mockery\MockInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\SiteManagement\Principal\Application\Exception\OperatorRoleNotAttachedException;
use Source\SiteManagement\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\SiteManagement\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator\RevokeSiteManagementOperator;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator\RevokeSiteManagementOperatorInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator\RevokeSiteManagementOperatorInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator\RevokeSiteManagementOperatorOutput;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\SiteManagementOperatorTestData;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RevokeSiteManagementOperatorTest extends TestCase
{
    public function testDeletesTheWholeTargetAccountOperatorGroupIncludingTheLastAdministrator(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = SiteManagementOperatorTestData::create();
        $data->operatorGroup->addRole($data->operatorRole);
        $data->operatorGroup->addMember($data->siteManagementPrincipal->principalIdentifier());
        $this->expectTarget($dependencies, $data, $data->operatorGroup);
        $dependencies->principalGroupRepository->shouldReceive('delete')->once()->with($data->operatorGroup);
        $dependencies->principalRepository->shouldNotReceive('save');

        $this->subject($dependencies)->process(
            new RevokeSiteManagementOperatorInput($data->email),
            new RevokeSiteManagementOperatorOutput(),
        );

        $this->assertCount(1, $data->operatorGroup->members());
    }

    public function testDoesNotCheckAccountOperationsMembershipBeforeRevocation(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = SiteManagementOperatorTestData::create();
        $data->operatorGroup->addRole($data->operatorRole);
        $data->operatorGroup->addMember($data->siteManagementPrincipal->principalIdentifier());
        $this->expectTarget($dependencies, $data, $data->operatorGroup);
        $dependencies->principalGroupRepository->shouldReceive('delete')->once();

        $this->subject($dependencies)->process(
            new RevokeSiteManagementOperatorInput($data->email),
            new RevokeSiteManagementOperatorOutput(),
        );

    }

    public function testScopesGroupLookupToTheTargetAccount(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = SiteManagementOperatorTestData::create();
        $data->operatorGroup->addRole($data->operatorRole);
        $data->operatorGroup->addMember($data->siteManagementPrincipal->principalIdentifier());
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturn($data->account);
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()->andReturn($data->operatorRole);
        $dependencies->principalGroupRepository->shouldReceive('findByAccountIdAndName')->once()
            ->with($data->account->accountIdentifier(), 'Operations SiteManagement Operators')
            ->andReturn($data->operatorGroup);
        $dependencies->principalGroupRepository->shouldReceive('delete')->once()->with($data->operatorGroup);

        $this->subject($dependencies)->process(
            new RevokeSiteManagementOperatorInput($data->email),
            new RevokeSiteManagementOperatorOutput(),
        );
    }

    public function testThrowsWhenAccountDoesNotExist(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = SiteManagementOperatorTestData::create();
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturnNull();

        $this->expectException(AccountNotFoundException::class);
        $this->subject($dependencies)->process(new RevokeSiteManagementOperatorInput($data->email), new RevokeSiteManagementOperatorOutput());
    }

    public function testThrowsWhenAdministratorSystemRoleDoesNotExist(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = SiteManagementOperatorTestData::create();
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturn($data->account);
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()->andReturnNull();

        $this->expectException(SystemRoleNotFoundException::class);
        $this->subject($dependencies)->process(new RevokeSiteManagementOperatorInput($data->email), new RevokeSiteManagementOperatorOutput());
    }

    public function testThrowsWhenOperatorGroupDoesNotExistIncludingRepeatedRevocation(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = SiteManagementOperatorTestData::create();
        $this->expectTarget($dependencies, $data, null);
        $dependencies->principalGroupRepository->shouldNotReceive('delete');

        $this->expectException(PrincipalGroupNotFoundException::class);
        $this->subject($dependencies)->process(new RevokeSiteManagementOperatorInput($data->email), new RevokeSiteManagementOperatorOutput());
    }

    public function testThrowsWhenOperatorRoleIsNotAttached(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = SiteManagementOperatorTestData::create();
        $data->operatorGroup->addMember($data->siteManagementPrincipal->principalIdentifier());
        $this->expectTarget($dependencies, $data, $data->operatorGroup);
        $dependencies->principalGroupRepository->shouldNotReceive('delete');

        $this->expectException(OperatorRoleNotAttachedException::class);
        $this->subject($dependencies)->process(new RevokeSiteManagementOperatorInput($data->email), new RevokeSiteManagementOperatorOutput());
    }

    public function testDeletesGroupAfterOriginalPrincipalWasRemoved(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = SiteManagementOperatorTestData::create();
        $data->operatorGroup->addRole($data->operatorRole);
        $data->operatorGroup->addMember(new PrincipalIdentifier(StrTestHelper::generateUuid()));
        $this->assertFalse($data->operatorGroup->hasMember($data->siteManagementPrincipal->principalIdentifier()));
        $this->expectTarget($dependencies, $data, $data->operatorGroup);
        $dependencies->principalGroupRepository->shouldReceive('delete')->once()->with($data->operatorGroup);

        $this->subject($dependencies)->process(new RevokeSiteManagementOperatorInput($data->email), new RevokeSiteManagementOperatorOutput());
    }

    private function expectTarget(
        RevokeDependencies $dependencies,
        SiteManagementOperatorTestData $data,
        ?PrincipalGroup $principalGroup,
    ): void {
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->with($data->email)->andReturn($data->account);
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()
            ->with('Operator')->andReturn($data->operatorRole);
        $dependencies->principalGroupRepository->shouldReceive('findByAccountIdAndName')->once()
            ->with($data->account->accountIdentifier(), 'Operations SiteManagement Operators')
            ->andReturn($principalGroup);
    }

    private function subject(RevokeDependencies $dependencies): RevokeSiteManagementOperator
    {
        $this->app()->instance(IdentityRepositoryInterface::class, $dependencies->identityRepository);
        $this->app()->instance(PrincipalRepositoryInterface::class, $dependencies->principalRepository);

        $this->app()->instance(AccountRepositoryInterface::class, $dependencies->accountRepository);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $dependencies->principalGroupRepository);
        $this->app()->instance(RoleRepositoryInterface::class, $dependencies->roleRepository);

        return $this->app()->make(RevokeSiteManagementOperatorInterface::class);
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
