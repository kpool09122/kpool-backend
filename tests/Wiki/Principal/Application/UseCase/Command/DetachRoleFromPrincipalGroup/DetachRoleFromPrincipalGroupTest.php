<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\DetachRoleFromPrincipalGroup;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\DetachRoleFromPrincipalGroup\DetachRoleFromPrincipalGroup;
use Source\Wiki\Principal\Application\UseCase\Command\DetachRoleFromPrincipalGroup\DetachRoleFromPrincipalGroupInput;
use Source\Wiki\Principal\Application\UseCase\Command\DetachRoleFromPrincipalGroup\DetachRoleFromPrincipalGroupInterface;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DetachRoleFromPrincipalGroupTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $useCase = $this->app->make(DetachRoleFromPrincipalGroupInterface::class);
        $this->assertInstanceOf(DetachRoleFromPrincipalGroup::class, $useCase);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        // Roleがアタッチされている状態
        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            false,
            new DateTimeImmutable(),
        );
        $principalGroup->addRole($roleIdentifier);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (PrincipalGroup $savedGroup) use ($roleIdentifier) {
                return ! $savedGroup->hasRole($roleIdentifier);
            }))
            ->andReturnNull();

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);

        $useCase = $this->app->make(DetachRoleFromPrincipalGroupInterface::class);
        $input = new DetachRoleFromPrincipalGroupInput($principalGroupIdentifier, $roleIdentifier);

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

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);

        $useCase = $this->app->make(DetachRoleFromPrincipalGroupInterface::class);
        $input = new DetachRoleFromPrincipalGroupInput($principalGroupIdentifier, $roleIdentifier);

        $this->expectException(PrincipalGroupNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * 正常系: アタッチされていないRoleでもエラーにならないこと（冪等性）
     *
     * @throws BindingResolutionException
     */
    public function testProcessIdempotent(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        // Roleがアタッチされていない状態
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
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (PrincipalGroup $savedGroup) {
                return count($savedGroup->roles()) === 0;
            }))
            ->andReturnNull();

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);

        $useCase = $this->app->make(DetachRoleFromPrincipalGroupInterface::class);
        $input = new DetachRoleFromPrincipalGroupInput($principalGroupIdentifier, $roleIdentifier);

        $useCase->process($input);
    }
}
