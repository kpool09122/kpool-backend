<?php

declare(strict_types=1);

namespace Tests\Account\PrincipalGroup\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Principal\Application\Exception\CannotRemoveLastOwnerException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroup;
use Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupInput;
use Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupInterface;
use Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupOutput;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Exception\PrincipalNotMemberException;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RemovePrincipalFromPrincipalGroupTest extends TestCase
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
        $useCase = $this->app->make(RemovePrincipalFromPrincipalGroupInterface::class);
        $this->assertInstanceOf(RemovePrincipalFromPrincipalGroup::class, $useCase);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            false,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember($principalIdentifier);

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);
        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn (PrincipalGroup $arg) => ! $arg->hasMember($principalIdentifier)))
            ->andReturnNull();
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->once()
            ->with(Role::OWNER)
            ->andReturn($this->createOwnerRole());

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $useCase = $this->app->make(RemovePrincipalFromPrincipalGroupInterface::class);
        $input = new RemovePrincipalFromPrincipalGroupInput($principalGroupIdentifier, $principalIdentifier);

        $output = new RemovePrincipalFromPrincipalGroupOutput();
        $useCase->process($input, $output);

        $result = $output->toArray();
        $this->assertNotContains((string) $principalIdentifier, $result['members']);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenNotFound(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturnNull();
        $repository->shouldNotReceive('save');

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);
        $this->app->instance(RoleRepositoryInterface::class, Mockery::mock(RoleRepositoryInterface::class));

        $useCase = $this->app->make(RemovePrincipalFromPrincipalGroupInterface::class);
        $input = new RemovePrincipalFromPrincipalGroupInput($principalGroupIdentifier, $principalIdentifier);

        $this->expectException(PrincipalGroupNotFoundException::class);

        $output = new RemovePrincipalFromPrincipalGroupOutput();
        $useCase->process($input, $output);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenNotMember(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

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
        $repository->shouldNotReceive('save');
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->once()
            ->with(Role::OWNER)
            ->andReturn($this->createOwnerRole());

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $useCase = $this->app->make(RemovePrincipalFromPrincipalGroupInterface::class);
        $input = new RemovePrincipalFromPrincipalGroupInput($principalGroupIdentifier, $principalIdentifier);

        $this->expectException(PrincipalNotMemberException::class);

        $output = new RemovePrincipalFromPrincipalGroupOutput();
        $useCase->process($input, $output);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenLastOwner(): void
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
        $principalGroup->addRole($ownerRole->roleIdentifier());
        $principalGroup->addMember($principalIdentifier);

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);
        $repository->shouldReceive('findByAccountId')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $accountIdentifier))
            ->andReturn([$principalGroup]); // Only one OWNER group with one member
        $repository->shouldNotReceive('save');
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->once()
            ->with(Role::OWNER)
            ->andReturn($ownerRole);

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $useCase = $this->app->make(RemovePrincipalFromPrincipalGroupInterface::class);
        $input = new RemovePrincipalFromPrincipalGroupInput($principalGroupIdentifier, $principalIdentifier);

        $this->expectException(CannotRemoveLastOwnerException::class);

        $output = new RemovePrincipalFromPrincipalGroupOutput();
        $useCase->process($input, $output);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testCanRemoveFromOwnerGroupWhenOtherOwnersExist(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $anotherPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $ownerRole = $this->createOwnerRole();

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Owner Group',
            false,
            new DateTimeImmutable(),
        );
        $principalGroup->addRole($ownerRole->roleIdentifier());
        $principalGroup->addMember($principalIdentifier);
        $principalGroup->addMember($anotherPrincipalIdentifier);

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);
        $repository->shouldReceive('findByAccountId')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $accountIdentifier))
            ->andReturn([$principalGroup]);
        $repository->shouldReceive('save')
            ->once()
            ->andReturnNull();
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->once()
            ->with(Role::OWNER)
            ->andReturn($ownerRole);

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $useCase = $this->app->make(RemovePrincipalFromPrincipalGroupInterface::class);
        $input = new RemovePrincipalFromPrincipalGroupInput($principalGroupIdentifier, $principalIdentifier);

        $output = new RemovePrincipalFromPrincipalGroupOutput();
        $useCase->process($input, $output);

        $result = $output->toArray();
        $this->assertNotContains((string) $principalIdentifier, $result['members']);
        $this->assertContains((string) $anotherPrincipalIdentifier, $result['members']);
    }

    private function createOwnerRole(): Role
    {
        return new Role(
            new RoleIdentifier(StrTestHelper::generateUuid()),
            Role::OWNER,
            [],
            true,
        );
    }
}
