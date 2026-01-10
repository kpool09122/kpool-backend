<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\AttachPolicyToRole;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Wiki\Principal\Application\Exception\PolicyNotFoundException;
use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\AttachPolicyToRole\AttachPolicyToRole;
use Source\Wiki\Principal\Application\UseCase\Command\AttachPolicyToRole\AttachPolicyToRoleInput;
use Source\Wiki\Principal\Application\UseCase\Command\AttachPolicyToRole\AttachPolicyToRoleInterface;
use Source\Wiki\Principal\Domain\Entity\Policy;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\Statement;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AttachPolicyToRoleTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $useCase = $this->app->make(AttachPolicyToRoleInterface::class);
        $this->assertInstanceOf(AttachPolicyToRole::class, $useCase);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());

        $role = new Role(
            $roleIdentifier,
            'Test Role',
            [],
            false,
            new DateTimeImmutable(),
        );

        $policy = new Policy(
            $policyIdentifier,
            'Test Policy',
            [
                new Statement(
                    Effect::ALLOW,
                    [Action::CREATE],
                    [ResourceType::TALENT],
                    null,
                ),
            ],
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
                return $savedRole->hasPolicy($policyIdentifier);
            }))
            ->andReturnNull();

        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $policyIdentifier))
            ->andReturn($policy);

        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);

        $useCase = $this->app->make(AttachPolicyToRoleInterface::class);
        $input = new AttachPolicyToRoleInput($roleIdentifier, $policyIdentifier);

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

        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository->shouldNotReceive('findById');

        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);

        $useCase = $this->app->make(AttachPolicyToRoleInterface::class);
        $input = new AttachPolicyToRoleInput($roleIdentifier, $policyIdentifier);

        $this->expectException(RoleNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testThrowsWhenPolicyNotFound(): void
    {
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());

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
        $roleRepository->shouldNotReceive('save');

        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $policyIdentifier))
            ->andReturnNull();

        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);

        $useCase = $this->app->make(AttachPolicyToRoleInterface::class);
        $input = new AttachPolicyToRoleInput($roleIdentifier, $policyIdentifier);

        $this->expectException(PolicyNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * 正常系: 既にアタッチされているPolicyでもエラーにならないこと（冪等性）
     *
     * @throws BindingResolutionException
     */
    public function testProcessIdempotent(): void
    {
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());

        // 既にPolicyがアタッチされている状態
        $role = new Role(
            $roleIdentifier,
            'Test Role',
            [$policyIdentifier],
            false,
            new DateTimeImmutable(),
        );

        $policy = new Policy(
            $policyIdentifier,
            'Test Policy',
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
            ->with(Mockery::on(function (Role $savedRole) use ($policyIdentifier) {
                // 重複追加されていないことを確認
                return count($savedRole->policies()) === 1 && $savedRole->hasPolicy($policyIdentifier);
            }))
            ->andReturnNull();

        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $policyIdentifier))
            ->andReturn($policy);

        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);

        $useCase = $this->app->make(AttachPolicyToRoleInterface::class);
        $input = new AttachPolicyToRoleInput($roleIdentifier, $policyIdentifier);

        $useCase->process($input);
    }
}
