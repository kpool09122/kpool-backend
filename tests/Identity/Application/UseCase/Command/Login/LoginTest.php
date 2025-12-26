<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\Login;

use DateTimeImmutable;
use DomainException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Identity\Application\UseCase\Command\Login\Login;
use Source\Identity\Application\UseCase\Command\Login\LoginInput;
use Source\Identity\Application\UseCase\Command\Login\LoginInterface;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Exception\UserNotFoundException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
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
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $this->app->instance(AuthServiceInterface::class, $authService);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
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
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUlid());
        $userName = new UserName('test-user');
        $email = new Email('user@example.com');
        $language = Language::KOREAN;
        $profileImage = new ImagePath('/resources/path/test.png');
        $plainPassword = new PlainPassword('PlainPass1!');
        $hashedPassword = HashedPassword::fromPlain($plainPassword);
        $emailVerifiedAt = new DateTimeImmutable();

        $input = new LoginInput($email, $plainPassword);

        $identity = new Identity(
            $identityIdentifier,
            $userName,
            $email,
            $language,
            $profileImage,
            $hashedPassword,
            $emailVerifiedAt,
        );

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($identity);

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('login')
            ->once()
            ->with($identity)
            ->andReturn($identity);

        $this->app->instance(AuthServiceInterface::class, $authService);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $useCase = $this->app->make(LoginInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($identity, $result);
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

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturnNull();

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldNotReceive('login');

        $this->app->instance(AuthServiceInterface::class, $authService);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
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
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUlid());
        $userName = new UserName('test-user');
        $email = new Email('user@example.com');
        $language = Language::KOREAN;
        $profileImage = new ImagePath('/resources/path/test.png');
        $plainPassword = new PlainPassword('PlainPass1!');
        $hashedPassword = HashedPassword::fromPlain($plainPassword);
        $emailVerifiedAt = null;

        $input = new LoginInput($email, $plainPassword);

        $identity = new Identity(
            $identityIdentifier,
            $userName,
            $email,
            $language,
            $profileImage,
            $hashedPassword,
            $emailVerifiedAt,
        );

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($identity);

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldNotReceive('login');

        $this->app->instance(AuthServiceInterface::class, $authService);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
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
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUlid());
        $userName = new UserName('test-user');
        $email = new Email('user@example.com');
        $language = Language::KOREAN;
        $profileImage = new ImagePath('/resources/path/test.png');
        $plainPassword = new PlainPassword('PlainPass1!');
        $hashedPassword = HashedPassword::fromPlain(new PlainPassword('NotSamePassword!'));
        $emailVerifiedAt = new DateTimeImmutable();

        $input = new LoginInput($email, $plainPassword);

        $identity = new Identity(
            $identityIdentifier,
            $userName,
            $email,
            $language,
            $profileImage,
            $hashedPassword,
            $emailVerifiedAt,
        );

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($identity);

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldNotReceive('login');

        $this->app->instance(AuthServiceInterface::class, $authService);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $useCase = $this->app->make(LoginInterface::class);

        $this->expectException(DomainException::class);

        $useCase->process($input);
    }
}
