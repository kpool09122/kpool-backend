<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\RegisterUser;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;
use Mockery;
use Source\Identity\Application\UseCase\Command\RegisterUser\RegisterUser;
use Source\Identity\Application\UseCase\Command\RegisterUser\RegisterUserInput;
use Source\Identity\Application\UseCase\Command\RegisterUser\RegisterUserInterface;
use Source\Identity\Domain\Entity\AuthCodeSession;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Exception\AlreadyUserExistsException;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Repository\AuthCodeSessionRepositoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
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
        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $authCodeSessionRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
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

        $identity = new Identity(
            new IdentityIdentifier(StrTestHelper::generateUlid()),
            $userName,
            $email,
            $language,
            null,
            HashedPassword::fromPlain($password),
            null,
        );

        $authCodeSessionRepository = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $authCodeSessionRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($session);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturnNull();
        $identityRepository->shouldReceive('save')
            ->once()
            ->with($identity)
            ->andReturnNull();

        $imagePath = new ImagePath('/path/to/profile.png');
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($base64EncodedImage)
            ->andReturn($imagePath);

        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $identityFactory->shouldReceive('create')
            ->once()
            ->with($userName, $email, $language, $password)
            ->andReturn($identity);

        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $authCodeSessionRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $useCase = $this->app->make(RegisterUserInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($identity, $result);
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

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldNotReceive('findByEmail');
        $identityRepository->shouldNotReceive('save');

        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $identityFactory->shouldNotReceive('create');

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldNotReceive('upload');

        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $authCodeSessionRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
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

        $existingIdentity = new Identity(
            new IdentityIdentifier(StrTestHelper::generateUlid()),
            $userName,
            $email,
            $language,
            null,
            HashedPassword::fromPlain($password),
            $verifiedAt,
        );

        $authCodeSessionRepository = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $authCodeSessionRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($session);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($existingIdentity);
        $identityRepository->shouldNotReceive('save');

        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $identityFactory->shouldNotReceive('create');

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldNotReceive('upload');

        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $authCodeSessionRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
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
        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);

        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $authCodeSessionRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
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

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturnNull();
        $identityRepository->shouldNotReceive('save');

        $identity = new Identity(
            new IdentityIdentifier(StrTestHelper::generateUlid()),
            $userName,
            $email,
            $language,
            null,
            HashedPassword::fromPlain($password),
            null,
        );
        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $identityFactory->shouldReceive('create')
            ->once()
            ->with($userName, $email, $language, $password)
            ->andReturn($identity);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldNotReceive('upload');

        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $authCodeSessionRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $useCase = $this->app->make(RegisterUserInterface::class);

        $this->expectException(UnauthorizedException::class);

        $useCase->process($input);
    }
}
