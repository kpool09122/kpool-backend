<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\AttachRoleToPrincipalGroup;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\AttachRoleToPrincipalGroup\AttachRoleToPrincipalGroup;
use Source\Wiki\Principal\Application\UseCase\Command\AttachRoleToPrincipalGroup\AttachRoleToPrincipalGroupInput;
use Source\Wiki\Principal\Application\UseCase\Command\AttachRoleToPrincipalGroup\AttachRoleToPrincipalGroupInterface;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AttachRoleToPrincipalGroupTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $useCase = $this->app->make(AttachRoleToPrincipalGroupInterface::class);
        $this->assertInstanceOf(AttachRoleToPrincipalGroup::class, $useCase);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            false,
            new DateTimeImmutable(),
        );

        $role = new Role(
            $roleIdentifier,
            'Test Role',
            [],
            false,
            new DateTimeImmutable(),
        );

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (PrincipalGroup $savedGroup) use ($roleIdentifier) {
                return $savedGroup->hasRole($roleIdentifier);
            }))
            ->andReturnNull();

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $roleIdentifier))
            ->andReturn($role);

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $useCase = $this->app->make(AttachRoleToPrincipalGroupInterface::class);
        $input = new AttachRoleToPrincipalGroupInput($principalGroupIdentifier, $roleIdentifier);

        $useCase->process($input);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testThrowsWhenPrincipalGroupNotFound(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturnNull();
        $principalGroupRepository->shouldNotReceive('save');

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldNotReceive('findById');

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $useCase = $this->app->make(AttachRoleToPrincipalGroupInterface::class);
        $input = new AttachRoleToPrincipalGroupInput($principalGroupIdentifier, $roleIdentifier);

        $this->expectException(PrincipalGroupNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testThrowsWhenRoleNotFound(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            false,
            new DateTimeImmutable(),
        );

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);
        $principalGroupRepository->shouldNotReceive('save');

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $roleIdentifier))
            ->andReturnNull();

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $useCase = $this->app->make(AttachRoleToPrincipalGroupInterface::class);
        $input = new AttachRoleToPrincipalGroupInput($principalGroupIdentifier, $roleIdentifier);

        $this->expectException(RoleNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * 正常系: 既にアタッチされているRoleでもエラーにならないこと（冪等性）
     *
     * @throws BindingResolutionException
     */
    public function testProcessIdempotent(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        // 既にRoleがアタッチされている状態
        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            false,
            new DateTimeImmutable(),
        );
        $principalGroup->addRole($roleIdentifier);

        $role = new Role(
            $roleIdentifier,
            'Test Role',
            [],
            false,
            new DateTimeImmutable(),
        );

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (PrincipalGroup $savedGroup) use ($roleIdentifier) {
                // 重複追加されていないことを確認
                return count($savedGroup->roles()) === 1 && $savedGroup->hasRole($roleIdentifier);
            }))
            ->andReturnNull();

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $roleIdentifier))
            ->andReturn($role);

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $useCase = $this->app->make(AttachRoleToPrincipalGroupInterface::class);
        $input = new AttachRoleToPrincipalGroupInput($principalGroupIdentifier, $roleIdentifier);

        $useCase->process($input);
    }
}
