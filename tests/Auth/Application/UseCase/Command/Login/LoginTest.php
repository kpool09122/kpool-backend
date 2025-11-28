<?php

declare(strict_types=1);

namespace Tests\Auth\Application\UseCase\Command\Login;

use DateTimeImmutable;
use DomainException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Auth\Application\UseCase\Command\Login\Login;
use Source\Auth\Application\UseCase\Command\Login\LoginInput;
use Source\Auth\Application\UseCase\Command\Login\LoginInterface;
use Source\Auth\Domain\Entity\User;
use Source\Auth\Domain\Exception\UserNotFoundException;
use Source\Auth\Domain\Repository\UserRepositoryInterface;
use Source\Auth\Domain\Service\AuthServiceInterface;
use Source\Auth\Domain\ValueObject\HashedPassword;
use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Auth\Domain\ValueObject\ServiceRole;
use Source\Auth\Domain\ValueObject\UserIdentifier;
use Source\Auth\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class LoginTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $authService = Mockery::mock(AuthServiceInterface::class);
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->app->instance(AuthServiceInterface::class, $authService);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $useCase = $this->app->make(LoginInterface::class);
        $this->assertInstanceOf(Login::class, $useCase);
    }

    /**
     * 正常系: 正しくログインできること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UserNotFoundException
     */
    public function testProcess(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $userName = new UserName('test-user');
        $email = new Email('user@example.com');
        $language = Language::KOREAN;
        $profileImage = new ImagePath('/resources/path/test.png');
        $plainPassword = new PlainPassword('PlainPass1!');
        $hashedPassword = HashedPassword::fromPlain($plainPassword);
        $serviceRoles = [new ServiceRole('auth', 'user'), new ServiceRole('auth', 'admin')];
        $emailVerifiedAt = new DateTimeImmutable();

        $input = new LoginInput($email, $plainPassword);

        $user = new User(
            $userIdentifier,
            $userName,
            $email,
            $language,
            $profileImage,
            $hashedPassword,
            $serviceRoles,
            $emailVerifiedAt,
        );

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($user);

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('login')
            ->once()
            ->with($user)
            ->andReturn($user);

        $this->app->instance(AuthServiceInterface::class, $authService);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $useCase = $this->app->make(LoginInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($user, $result);
    }

    /**
     * 異常系: メールアドレスが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsUserNotFoundException(): void
    {
        $email = new Email('user@example.com');
        $password = new PlainPassword('PlainPass1!');
        $input = new LoginInput($email, $password);

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturnNull();

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldNotReceive('login');

        $this->app->instance(AuthServiceInterface::class, $authService);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $useCase = $this->app->make(LoginInterface::class);

        $this->expectException(UserNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * 異常系: メールアドレスが認証されていない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UserNotFoundException
     */
    public function testWhenEmailIsNotVerified(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $userName = new UserName('test-user');
        $email = new Email('user@example.com');
        $language = Language::KOREAN;
        $profileImage = new ImagePath('/resources/path/test.png');
        $plainPassword = new PlainPassword('PlainPass1!');
        $hashedPassword = HashedPassword::fromPlain($plainPassword);
        $serviceRoles = [new ServiceRole('auth', 'user'), new ServiceRole('auth', 'admin')];
        $emailVerifiedAt = null;

        $input = new LoginInput($email, $plainPassword);

        $user = new User(
            $userIdentifier,
            $userName,
            $email,
            $language,
            $profileImage,
            $hashedPassword,
            $serviceRoles,
            $emailVerifiedAt,
        );

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($user);

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldNotReceive('login');

        $this->app->instance(AuthServiceInterface::class, $authService);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $useCase = $this->app->make(LoginInterface::class);

        $this->expectException(DomainException::class);

        $useCase->process($input);
    }

    /**
     * 異常系: パスワード認証に失敗した場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UserNotFoundException
     */
    public function testWhenFailedToVerifyPassword(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $userName = new UserName('test-user');
        $email = new Email('user@example.com');
        $language = Language::KOREAN;
        $profileImage = new ImagePath('/resources/path/test.png');
        $plainPassword = new PlainPassword('PlainPass1!');
        $hashedPassword = HashedPassword::fromPlain(new PlainPassword('NotSamePassword!'));
        $serviceRoles = [new ServiceRole('auth', 'user'), new ServiceRole('auth', 'admin')];
        $emailVerifiedAt = new DateTimeImmutable();

        $input = new LoginInput($email, $plainPassword);

        $user = new User(
            $userIdentifier,
            $userName,
            $email,
            $language,
            $profileImage,
            $hashedPassword,
            $serviceRoles,
            $emailVerifiedAt,
        );

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($user);

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldNotReceive('login');

        $this->app->instance(AuthServiceInterface::class, $authService);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $useCase = $this->app->make(LoginInterface::class);

        $this->expectException(DomainException::class);

        $useCase->process($input);
    }
}
