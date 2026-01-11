<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\DetachPolicyFromRole;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\DetachPolicyFromRole\DetachPolicyFromRole;
use Source\Wiki\Principal\Application\UseCase\Command\DetachPolicyFromRole\DetachPolicyFromRoleInput;
use Source\Wiki\Principal\Application\UseCase\Command\DetachPolicyFromRole\DetachPolicyFromRoleInterface;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DetachPolicyFromRoleTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $useCase = $this->app->make(DetachPolicyFromRoleInterface::class);
        $this->assertInstanceOf(DetachPolicyFromRole::class, $useCase);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());

        // Policyがアタッチされている状態
        $role = new Role(
            $roleIdentifier,
            'Test Role',
            [$policyIdentifier],
            false,
            new DateTimeImmutable(),
        );

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $roleIdentifier))
            ->andReturn($role);
        $roleRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Role $savedRole) use ($policyIdentifier) {
                return ! $savedRole->hasPolicy($policyIdentifier);
            }))
            ->andReturnNull();

        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $useCase = $this->app->make(DetachPolicyFromRoleInterface::class);
        $input = new DetachPolicyFromRoleInput($roleIdentifier, $policyIdentifier);

        $useCase->process($input);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testThrowsWhenRoleNotFound(): void
    {
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $roleIdentifier))
            ->andReturnNull();
        $roleRepository->shouldNotReceive('save');

        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $useCase = $this->app->make(DetachPolicyFromRoleInterface::class);
        $input = new DetachPolicyFromRoleInput($roleIdentifier, $policyIdentifier);

        $this->expectException(RoleNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * 正常系: アタッチされていないPolicyでもエラーにならないこと（冪等性）
     *
     * @throws BindingResolutionException
     */
    public function testProcessIdempotent(): void
    {
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());

        // Policyがアタッチされていない状態
        $role = new Role(
            $roleIdentifier,
            'Test Role',
            [],
            false,
            new DateTimeImmutable(),
        );

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $roleIdentifier))
            ->andReturn($role);
        $roleRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Role $savedRole) {
                return count($savedRole->policies()) === 0;
            }))
            ->andReturnNull();

        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $useCase = $this->app->make(DetachPolicyFromRoleInterface::class);
        $input = new DetachPolicyFromRoleInput($roleIdentifier, $policyIdentifier);

        $useCase->process($input);
    }
}
