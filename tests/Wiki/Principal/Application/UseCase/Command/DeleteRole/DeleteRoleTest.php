<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\DeleteRole;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Wiki\Principal\Application\Exception\CannotDeleteSystemRoleException;
use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\DeleteRole\DeleteRole;
use Source\Wiki\Principal\Application\UseCase\Command\DeleteRole\DeleteRoleInput;
use Source\Wiki\Principal\Application\UseCase\Command\DeleteRole\DeleteRoleInterface;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DeleteRoleTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(RoleRepositoryInterface::class);
        $this->app->instance(RoleRepositoryInterface::class, $repository);
        $useCase = $this->app->make(DeleteRoleInterface::class);
        $this->assertInstanceOf(DeleteRole::class, $useCase);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());

        $role = new Role(
            $roleIdentifier,
            'Test Role',
            [],
            false, // isSystemRole = false
            new DateTimeImmutable(),
        );

        $repository = Mockery::mock(RoleRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $roleIdentifier))
            ->andReturn($role);
        $repository->shouldReceive('delete')
            ->once()
            ->with($role)
            ->andReturnNull();

        $this->app->instance(RoleRepositoryInterface::class, $repository);

        $useCase = $this->app->make(DeleteRoleInterface::class);
        $input = new DeleteRoleInput($roleIdentifier);

        $useCase->process($input);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testThrowsWhenNotFound(): void
    {
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());

        $repository = Mockery::mock(RoleRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $roleIdentifier))
            ->andReturnNull();
        $repository->shouldNotReceive('delete');

        $this->app->instance(RoleRepositoryInterface::class, $repository);

        $useCase = $this->app->make(DeleteRoleInterface::class);
        $input = new DeleteRoleInput($roleIdentifier);

        $this->expectException(RoleNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testThrowsWhenSystemRole(): void
    {
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());

        $role = new Role(
            $roleIdentifier,
            'System Role',
            [],
            true, // isSystemRole = true
            new DateTimeImmutable(),
        );

        $repository = Mockery::mock(RoleRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $roleIdentifier))
            ->andReturn($role);
        $repository->shouldNotReceive('delete');

        $this->app->instance(RoleRepositoryInterface::class, $repository);

        $useCase = $this->app->make(DeleteRoleInterface::class);
        $input = new DeleteRoleInput($roleIdentifier);

        $this->expectException(CannotDeleteSystemRoleException::class);

        $useCase->process($input);
    }
}
