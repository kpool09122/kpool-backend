<?php

declare(strict_types=1);

namespace Tests\SiteManagement\User\Application\UseCase\Command\ProvisionUser;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\User\Application\UseCase\Command\ProvisionUser\ProvisionUser;
use Source\SiteManagement\User\Application\UseCase\Command\ProvisionUser\ProvisionUserInput;
use Source\SiteManagement\User\Application\UseCase\Command\ProvisionUser\ProvisionUserInterface;
use Source\SiteManagement\User\Domain\Entity\User;
use Source\SiteManagement\User\Domain\Exception\AlreadyUserExistsException;
use Source\SiteManagement\User\Domain\Factory\UserFactoryInterface;
use Source\SiteManagement\User\Domain\Repository\UserRepositoryInterface;
use Source\SiteManagement\User\Domain\ValueObject\Role;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ProvisionUserTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userFactory = Mockery::mock(UserFactoryInterface::class);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $this->app->instance(UserFactoryInterface::class, $userFactory);

        $provisionUser = $this->app->make(ProvisionUserInterface::class);
        $this->assertInstanceOf(ProvisionUser::class, $provisionUser);
    }

    /**
     * 正常系：ユーザーが存在しない場合、新規ユーザーが作成されて保存されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AlreadyUserExistsException
     */
    public function testProcess(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $input = new ProvisionUserInput($identityIdentifier);

        $userIdentifier = new UserIdentifier(StrTestHelper::generateUuid());
        $user = new User(
            $userIdentifier,
            $identityIdentifier,
            Role::NONE,
        );

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findByIdentityIdentifier')
            ->once()
            ->with($identityIdentifier)
            ->andReturn(null);
        $userRepository->shouldReceive('save')
            ->once()
            ->with($user)
            ->andReturn(null);

        $userFactory = Mockery::mock(UserFactoryInterface::class);
        $userFactory->shouldReceive('create')
            ->once()
            ->with($identityIdentifier)
            ->andReturn($user);

        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $this->app->instance(UserFactoryInterface::class, $userFactory);

        $provisionUser = $this->app->make(ProvisionUserInterface::class);
        $result = $provisionUser->process($input);

        $this->assertSame($user, $result);
    }

    /**
     * 異常系：ユーザーが既に存在する場合、AlreadyUserExistsExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessWhenUserAlreadyExists(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $input = new ProvisionUserInput($identityIdentifier);

        $userIdentifier = new UserIdentifier(StrTestHelper::generateUuid());
        $existingUser = new User(
            $userIdentifier,
            $identityIdentifier,
            Role::NONE,
        );

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findByIdentityIdentifier')
            ->once()
            ->with($identityIdentifier)
            ->andReturn($existingUser);

        $userFactory = Mockery::mock(UserFactoryInterface::class);

        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $this->app->instance(UserFactoryInterface::class, $userFactory);

        $this->expectException(AlreadyUserExistsException::class);

        $provisionUser = $this->app->make(ProvisionUserInterface::class);
        $provisionUser->process($input);
    }
}
