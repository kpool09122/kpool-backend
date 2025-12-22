<?php

declare(strict_types=1);

namespace Tests\Auth\Application\UseCase\Command\SendAuthCode;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Auth\Application\UseCase\Command\SendAuthCode\SendAuthCode;
use Source\Auth\Application\UseCase\Command\SendAuthCode\SendAuthCodeInput;
use Source\Auth\Application\UseCase\Command\SendAuthCode\SendAuthCodeInterface;
use Source\Auth\Domain\Entity\AuthCodeSession;
use Source\Auth\Domain\Entity\User;
use Source\Auth\Domain\Factory\AuthCodeSessionFactoryInterface;
use Source\Auth\Domain\Repository\AuthCodeSessionRepositoryInterface;
use Source\Auth\Domain\Repository\UserRepositoryInterface;
use Source\Auth\Domain\Service\AuthCodeServiceInterface;
use Source\Auth\Domain\ValueObject\AuthCode;
use Source\Auth\Domain\ValueObject\HashedPassword;
use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Auth\Domain\ValueObject\ServiceRole;
use Source\Auth\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SendAuthCodeTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $authCodeService = Mockery::mock(AuthCodeServiceInterface::class);
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $authCodeSessionRepository = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $authCodeSessionFactory = Mockery::mock(AuthCodeSessionFactoryInterface::class);
        $this->app->instance(AuthCodeServiceInterface::class, $authCodeService);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $authCodeSessionRepository);
        $this->app->instance(AuthCodeSessionFactoryInterface::class, $authCodeSessionFactory);

        $useCase = $this->app->make(SendAuthCodeInterface::class);

        $this->assertInstanceOf(SendAuthCode::class, $useCase);
    }

    /**
     * 正常系: ユーザー未登録なら認証コード生成と送信を行うこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $email = new Email('user@example.com');
        $language = Language::KOREAN;
        $input = new SendAuthCodeInput($email, $language);

        $generatedAt = new DateTimeImmutable();
        $authCode = new AuthCode('123456');
        $session = new AuthCodeSession($email, $authCode, $generatedAt);

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturnNull();

        $authCodeService = Mockery::mock(AuthCodeServiceInterface::class);
        $authCodeService->shouldReceive('generateCode')
            ->once()
            ->with($email)
            ->andReturn($authCode);
        $authCodeService->shouldReceive('send')
            ->once()
            ->with($email, $language, $session)
            ->andReturnNull();

        $authCodeSessionFactory = Mockery::mock(AuthCodeSessionFactoryInterface::class);
        $authCodeSessionFactory->shouldReceive('create')
            ->once()
            ->with($email, $authCode)
            ->andReturn($session);

        $authCodeSessionRepository = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $authCodeSessionRepository->shouldReceive('save')
            ->once()
            ->with($session)
            ->andReturnNull();

        $this->app->instance(AuthCodeServiceInterface::class, $authCodeService);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $authCodeSessionRepository);
        $this->app->instance(AuthCodeSessionFactoryInterface::class, $authCodeSessionFactory);

        $useCase = $this->app->make(SendAuthCodeInterface::class);

        $useCase->process($input);
    }

    /**
     * 正常系: 登録済みメールアドレスの場合は重複通知のみ送ること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testWhenEmailAlreadyExists(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $userName = new UserName('test-user');
        $email = new Email('user@example.com');
        $language = Language::JAPANESE;
        $profileImage = new ImagePath('/resources/path/test.png');
        $plainPassword = new PlainPassword('PlainPass1!');
        $hashedPassword = HashedPassword::fromPlain($plainPassword);
        $serviceRoles = [new ServiceRole('auth', 'user')];
        $emailVerifiedAt = new DateTimeImmutable();
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

        $input = new SendAuthCodeInput($email, $language);

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($user);

        $authCodeService = Mockery::mock(AuthCodeServiceInterface::class);
        $authCodeService->shouldReceive('notifyConflict')
            ->once()
            ->with($email, $language)
            ->andReturnNull();
        $authCodeService->shouldNotReceive('generateCode');
        $authCodeService->shouldNotReceive('send');
        $authCodeSessionFactory = Mockery::mock(AuthCodeSessionFactoryInterface::class);
        $authCodeSessionFactory->shouldNotReceive('create');

        $authCodeSessionRepository = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $authCodeSessionRepository->shouldNotReceive('save');

        $this->app->instance(AuthCodeServiceInterface::class, $authCodeService);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $authCodeSessionRepository);
        $this->app->instance(AuthCodeSessionFactoryInterface::class, $authCodeSessionFactory);
        $useCase = $this->app->make(SendAuthCodeInterface::class);

        $useCase->process($input);
    }
}
