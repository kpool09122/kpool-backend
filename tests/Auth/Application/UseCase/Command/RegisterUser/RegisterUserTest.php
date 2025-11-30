<?php

declare(strict_types=1);

namespace Tests\Auth\Application\UseCase\Command\RegisterUser;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;
use Mockery;
use Source\Auth\Application\UseCase\Command\RegisterUser\RegisterUser;
use Source\Auth\Application\UseCase\Command\RegisterUser\RegisterUserInput;
use Source\Auth\Application\UseCase\Command\RegisterUser\RegisterUserInterface;
use Source\Auth\Domain\Entity\AuthCodeSession;
use Source\Auth\Domain\Entity\User;
use Source\Auth\Domain\Exception\AlreadyUserExistsException;
use Source\Auth\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Auth\Domain\Factory\UserFactoryInterface;
use Source\Auth\Domain\Repository\AuthCodeSessionRepositoryInterface;
use Source\Auth\Domain\Repository\UserRepositoryInterface;
use Source\Auth\Domain\ValueObject\AuthCode;
use Source\Auth\Domain\ValueObject\HashedPassword;
use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Auth\Domain\ValueObject\UserName;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\UserIdentifier;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RegisterUserTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $authCodeSessionRepository = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $userFactory = Mockery::mock(UserFactoryInterface::class);
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $authCodeSessionRepository);
        $this->app->instance(UserFactoryInterface::class, $userFactory);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $useCase = $this->app->make(RegisterUserInterface::class);
        $this->assertInstanceOf(RegisterUser::class, $useCase);
    }

    /**
     * 正常系: 認証済みセッションが存在し、ユーザーが未登録なら登録処理が行われること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AuthCodeSessionNotFoundException
     * @throws UnauthorizedException
     * @throws AlreadyUserExistsException
     */
    public function testProcess(): void
    {
        $userName = new UserName('test-user');
        $email = new Email('user@example.com');
        $language = Language::JAPANESE;
        $password = new PlainPassword('PlainPass1!');
        $confirmedPassword = new PlainPassword('PlainPass1!');
        $base64EncodedImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/w8AAusB9xyxOvUAAAAASUVORK5CYII=';
        $input = new RegisterUserInput(
            $userName,
            $email,
            $language,
            $password,
            $confirmedPassword,
            $base64EncodedImage,
        );

        $verifiedAt = new DateTimeImmutable();
        $authCode = new AuthCode('123456');
        $session = new AuthCodeSession($email, $authCode, $verifiedAt, $verifiedAt);

        $user = new User(
            new UserIdentifier(StrTestHelper::generateUlid()),
            $userName,
            $email,
            $language,
            null,
            HashedPassword::fromPlain($password),
            [],
            null,
        );

        $authCodeSessionRepository = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $authCodeSessionRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($session);

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturnNull();
        $userRepository->shouldReceive('save')
            ->once()
            ->with($user)
            ->andReturnNull();

        $imagePath = new ImagePath('/path/to/profile.png');
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($base64EncodedImage)
            ->andReturn($imagePath);

        $userFactory = Mockery::mock(UserFactoryInterface::class);
        $userFactory->shouldReceive('create')
            ->once()
            ->with($userName, $email, $language, $password)
            ->andReturn($user);

        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $authCodeSessionRepository);
        $this->app->instance(UserFactoryInterface::class, $userFactory);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $useCase = $this->app->make(RegisterUserInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($user, $result);
        $this->assertSame($session->verifiedAt(), $result->emailVerifiedAt());
        $this->assertSame($imagePath, $result->profileImage());
    }

    /**
     * 異常系: 認証コードセッションが見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws AlreadyUserExistsException
     */
    public function testThrowsAuthCodeSessionNotFoundException(): void
    {
        $userName = new UserName('test-user');
        $email = new Email('user@example.com');
        $language = Language::JAPANESE;
        $password = new PlainPassword('PlainPass1!');
        $confirmedPassword = new PlainPassword('PlainPass1!');
        $base64EncodedImage = null;
        $input = new RegisterUserInput(
            $userName,
            $email,
            $language,
            $password,
            $confirmedPassword,
            $base64EncodedImage,
        );

        $authCodeSessionRepository = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $authCodeSessionRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturnNull();

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldNotReceive('findByEmail');
        $userRepository->shouldNotReceive('save');

        $userFactory = Mockery::mock(UserFactoryInterface::class);
        $userFactory->shouldNotReceive('create');

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldNotReceive('upload');

        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $authCodeSessionRepository);
        $this->app->instance(UserFactoryInterface::class, $userFactory);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $useCase = $this->app->make(RegisterUserInterface::class);

        $this->expectException(AuthCodeSessionNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * 異常系: 既に同じメールのユーザーが存在するとき、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AuthCodeSessionNotFoundException
     * @throws UnauthorizedException
     */
    public function testThrowsAlreadyUserExistsException(): void
    {
        $userName = new UserName('test-user');
        $email = new Email('user@example.com');
        $language = Language::JAPANESE;
        $password = new PlainPassword('PlainPass1!');
        $confirmedPassword = new PlainPassword('PlainPass1!');
        $base64EncodedImage = null;
        $input = new RegisterUserInput(
            $userName,
            $email,
            $language,
            $password,
            $confirmedPassword,
            $base64EncodedImage,
        );

        $verifiedAt = new DateTimeImmutable();
        $session = new AuthCodeSession($email, new AuthCode('123456'), $verifiedAt, $verifiedAt);

        $existingUser = new User(
            new UserIdentifier(StrTestHelper::generateUlid()),
            $userName,
            $email,
            $language,
            null,
            HashedPassword::fromPlain($password),
            [],
            $verifiedAt,
        );

        $authCodeSessionRepository = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $authCodeSessionRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($session);

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($existingUser);
        $userRepository->shouldNotReceive('save');

        $userFactory = Mockery::mock(UserFactoryInterface::class);
        $userFactory->shouldNotReceive('create');

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldNotReceive('upload');

        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $authCodeSessionRepository);
        $this->app->instance(UserFactoryInterface::class, $userFactory);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $useCase = $this->app->make(RegisterUserInterface::class);

        $this->expectException(AlreadyUserExistsException::class);

        $useCase->process($input);
    }

    /**
     * 異常系: パスワードと確認用パスワードが一致しないとき、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AuthCodeSessionNotFoundException
     * @throws UnauthorizedException
     * @throws AlreadyUserExistsException
     */
    public function testThrowsInvalidArgumentExceptionWhenPasswordsMatch(): void
    {
        $userName = new UserName('test-user');
        $email = new Email('user@example.com');
        $language = Language::JAPANESE;
        $password = new PlainPassword('PlainPass1!');
        $confirmedPassword = new PlainPassword('PlainPass2@');
        $base64EncodedImage = null;
        $input = new RegisterUserInput(
            $userName,
            $email,
            $language,
            $password,
            $confirmedPassword,
            $base64EncodedImage,
        );

        $authCodeSessionRepository = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $userFactory = Mockery::mock(UserFactoryInterface::class);
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $userRepository = Mockery::mock(UserRepositoryInterface::class);

        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $authCodeSessionRepository);
        $this->app->instance(UserFactoryInterface::class, $userFactory);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $useCase = $this->app->make(RegisterUserInterface::class);

        $this->expectException(InvalidArgumentException::class);

        $useCase->process($input);
    }

    /**
     * 異常系: 認証済みでないセッションの場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AuthCodeSessionNotFoundException
     * @throws AlreadyUserExistsException
     */
    public function testThrowsUnauthorizedExceptionWhenSessionIsNotVerified(): void
    {
        $userName = new UserName('test-user');
        $email = new Email('user@example.com');
        $language = Language::JAPANESE;
        $password = new PlainPassword('PlainPass1!');
        $confirmedPassword = new PlainPassword('PlainPass1!');
        $base64EncodedImage = null;
        $input = new RegisterUserInput(
            $userName,
            $email,
            $language,
            $password,
            $confirmedPassword,
            $base64EncodedImage,
        );

        $generatedAt = new DateTimeImmutable();
        $session = new AuthCodeSession($email, new AuthCode('123456'), $generatedAt);

        $authCodeSessionRepository = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $authCodeSessionRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($session);

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturnNull();
        $userRepository->shouldNotReceive('save');

        $user = new User(
            new UserIdentifier(StrTestHelper::generateUlid()),
            $userName,
            $email,
            $language,
            null,
            HashedPassword::fromPlain($password),
            [],
            null,
        );
        $userFactory = Mockery::mock(UserFactoryInterface::class);
        $userFactory->shouldReceive('create')
            ->once()
            ->with($userName, $email, $language, $password)
            ->andReturn($user);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldNotReceive('upload');

        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $authCodeSessionRepository);
        $this->app->instance(UserFactoryInterface::class, $userFactory);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $useCase = $this->app->make(RegisterUserInterface::class);

        $this->expectException(UnauthorizedException::class);

        $useCase->process($input);
    }
}
