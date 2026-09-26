<?php

declare(strict_types=1);

namespace Tests\Account\PrincipalGroup\Application\UseCase\Command\DeletePrincipalGroup;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Principal\Application\Exception\CannotDeleteDefaultPrincipalGroupException;
use Source\Account\Principal\Application\Exception\CannotDeleteLastOwnerGroupException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Application\UseCase\Command\DeletePrincipalGroup\DeletePrincipalGroup;
use Source\Account\Principal\Application\UseCase\Command\DeletePrincipalGroup\DeletePrincipalGroupInput;
use Source\Account\Principal\Application\UseCase\Command\DeletePrincipalGroup\DeletePrincipalGroupInterface;
use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Exception\SystemRoleNotFoundException;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DeletePrincipalGroupTest extends TestCase
{
    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $useCase = $this->app->make(DeletePrincipalGroupInterface::class);
        $this->assertInstanceOf(DeletePrincipalGroup::class, $useCase);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            false,
            new DateTimeImmutable(),
        );

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);
        $repository->shouldReceive('delete')
            ->once()
            ->with($principalGroup)
            ->andReturnNull();
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findSystemByName')
            ->once()
            ->with(Role::OWNER)
            ->andReturn($this->createOwnerRole());

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $useCase = $this->app->make(DeletePrincipalGroupInterface::class);
        $input = new DeletePrincipalGroupInput($principalGroupIdentifier);

        $useCase->process($input);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenNotFound(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturnNull();
        $repository->shouldNotReceive('delete');

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);
        $this->app->instance(RoleRepositoryInterface::class, Mockery::mock(RoleRepositoryInterface::class));

        $useCase = $this->app->make(DeletePrincipalGroupInterface::class);
        $input = new DeletePrincipalGroupInput($principalGroupIdentifier);

        $this->expectException(PrincipalGroupNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenDefaultGroup(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Default Owner Group',
            true, // isDefault = true
            new DateTimeImmutable(),
        );

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);
        $repository->shouldNotReceive('delete');

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);
        $this->app->instance(RoleRepositoryInterface::class, Mockery::mock(RoleRepositoryInterface::class));

        $useCase = $this->app->make(DeletePrincipalGroupInterface::class);
        $input = new DeletePrincipalGroupInput($principalGroupIdentifier);

        $this->expectException(CannotDeleteDefaultPrincipalGroupException::class);

        $useCase->process($input);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenLastOwnerGroup(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $ownerRole = $this->createOwnerRole();

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Owner Group',
            false,
            new DateTimeImmutable(),
        );
        $principalGroup->addRole($ownerRole);
        $principalGroup->addMember($principalIdentifier);

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);
        $repository->shouldReceive('findByAccountId')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $accountIdentifier))
            ->andReturn([$principalGroup]); // Only one OWNER group with members
        $repository->shouldNotReceive('delete');
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findSystemByName')
            ->once()
            ->with(Role::OWNER)
            ->andReturn($ownerRole);

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $useCase = $this->app->make(DeletePrincipalGroupInterface::class);
        $input = new DeletePrincipalGroupInput($principalGroupIdentifier);

        $this->expectException(CannotDeleteLastOwnerGroupException::class);

        $useCase->process($input);
    }

    public function testThrowsDedicatedExceptionWhenOwnerRoleIsMissing(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            false,
            new DateTimeImmutable(),
        );

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);
        $repository->shouldNotReceive('delete');

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findSystemByName')
            ->once()
            ->with(Role::OWNER)
            ->andReturnNull();

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $this->expectException(SystemRoleNotFoundException::class);
        $this->expectExceptionMessage('Owner account role is not found.');

        $useCase = $this->app->make(DeletePrincipalGroupInterface::class);
        $input = new DeletePrincipalGroupInput($principalGroupIdentifier);
        $useCase->process($input);
    }

    public function testDeletesDedicatedRoleAndPolicyWithDelegationGroup(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $role = new Role(new RoleIdentifier(StrTestHelper::generateUuid()), 'Delegation Role', [], $accountIdentifier);
        $policy = new Policy(
            new PolicyIdentifier(StrTestHelper::generateUuid()),
            'Delegation Policy',
            [],
            $accountIdentifier,
            new DateTimeImmutable(),
        );
        $role->addPolicy($policy);
        $group = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Delegation Group',
            false,
            new DateTimeImmutable(),
            [$role->roleIdentifier()],
            new DelegationIdentifier(StrTestHelper::generateUuid()),
        );

        $groups = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $groups->shouldReceive('findById')->once()->andReturn($group);
        $groups->shouldReceive('delete')->once()->with($group);
        $roles = Mockery::mock(RoleRepositoryInterface::class);
        $roles->shouldReceive('findSystemByName')->once()->with(Role::OWNER)->andReturn($this->createOwnerRole());
        $roles->shouldReceive('findByIds')->once()->with([$role->roleIdentifier()])->andReturn([(string) $role->roleIdentifier() => $role]);
        $roles->shouldReceive('delete')->once()->with($role);
        $policies = Mockery::mock(PolicyRepositoryInterface::class);
        $policies->shouldReceive('findByIds')->once()->with([$policy->policyIdentifier()])->andReturn([(string) $policy->policyIdentifier() => $policy]);
        $policies->shouldReceive('delete')->once()->with($policy);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $groups);
        $this->app->instance(RoleRepositoryInterface::class, $roles);
        $this->app->instance(PolicyRepositoryInterface::class, $policies);

        $this->app->make(DeletePrincipalGroupInterface::class)->process(
            new DeletePrincipalGroupInput($group->principalGroupIdentifier()),
        );
    }

    private function createOwnerRole(): Role
    {
        return new Role(
            new RoleIdentifier(StrTestHelper::generateUuid()),
            Role::OWNER,
            [],
            null,
        );
    }
}
