<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\VerifyEmail;

use DateTimeImmutable;
use DomainException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Identity\Application\UseCase\Command\VerifyEmail\VerifyEmail;
use Source\Identity\Application\UseCase\Command\VerifyEmail\VerifyEmailInput;
use Source\Identity\Application\UseCase\Command\VerifyEmail\VerifyEmailInterface;
use Source\Identity\Domain\Entity\AuthCodeSession;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Identity\Domain\Factory\AuthCodeSessionFactoryInterface;
use Source\Identity\Domain\Repository\AuthCodeSessionRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $factory = Mockery::mock(AuthCodeSessionFactoryInterface::class);

        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $repository);
        $this->app->instance(AuthCodeSessionFactoryInterface::class, $factory);

        $useCase = $this->app->make(VerifyEmailInterface::class);

        $this->assertInstanceOf(VerifyEmail::class, $useCase);
    }

    /**
     * 正常系: セッションを検証し、verified 版を生成して保存・返却すること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AuthCodeSessionNotFoundException
     */
    public function testProcess(): void
    {
        $email = new Email('user@example.com');
        $authCode = new AuthCode('123456');
        $generatedAt = new DateTimeImmutable('-5 minutes');
        $existingSession = new AuthCodeSession($email, $authCode, $generatedAt);

        $verifiedAt = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
        $verifiedSession = new AuthCodeSession($email, $authCode, $verifiedAt, $verifiedAt);

        $repository = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($existingSession);
        $repository->shouldReceive('delete')
            ->once()
            ->with($email);
        $repository->shouldReceive('save')
            ->once()
            ->with($verifiedSession);

        $factory = Mockery::mock(AuthCodeSessionFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->with($email, $authCode, Mockery::type(DateTimeImmutable::class))
            ->andReturn($verifiedSession);

        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $repository);
        $this->app->instance(AuthCodeSessionFactoryInterface::class, $factory);

        $useCase = $this->app->make(VerifyEmailInterface::class);
        $input = new VerifyEmailInput($email, $authCode);

        $result = $useCase->process($input);

        $this->assertSame($verifiedSession, $result);
        $this->assertSame($verifiedAt, $result->verifiedAt());
    }

    /**
     * 異常系: セッション未登録の場合に例外を投げること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessWhenSessionNotFound(): void
    {
        $email = new Email('user@example.com');
        $authCode = new AuthCode('123456');
        $input = new VerifyEmailInput($email, $authCode);

        $repository = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturnNull();
        $repository->shouldNotReceive('delete');
        $repository->shouldNotReceive('save');

        $factory = Mockery::mock(AuthCodeSessionFactoryInterface::class);
        $factory->shouldNotReceive('create');

        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $repository);
        $this->app->instance(AuthCodeSessionFactoryInterface::class, $factory);
        $useCase = $this->app->make(VerifyEmailInterface::class);

        $this->expectException(AuthCodeSessionNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * 異常系: 有効期限切れの場合に例外を投げること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AuthCodeSessionNotFoundException
     */
    public function testProcessWhenSessionExpired(): void
    {
        $email = new Email('user@example.com');
        $authCode = new AuthCode('123456');
        $generatedAt = new DateTimeImmutable('-20 minutes');
        $expiredSession = new AuthCodeSession($email, $authCode, $generatedAt);
        $input = new VerifyEmailInput($email, $authCode);

        $repository = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($expiredSession);
        $repository->shouldNotReceive('delete');
        $repository->shouldNotReceive('save');

        $factory = Mockery::mock(AuthCodeSessionFactoryInterface::class);
        $factory->shouldNotReceive('create');

        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $repository);
        $this->app->instance(AuthCodeSessionFactoryInterface::class, $factory);
        $useCase = $this->app->make(VerifyEmailInterface::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('認証コードの有効期限が切れています。');

        $useCase->process($input);
    }

    /**
     * 異常系: 認証コード不一致の場合に例外を投げること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AuthCodeSessionNotFoundException
     */
    public function testProcessWhenAuthCodeMismatch(): void
    {
        $email = new Email('user@example.com');
        $authCode = new AuthCode('123456');
        $inputCode = new AuthCode('654321');
        $generatedAt = new DateTimeImmutable('-5 minutes');
        $session = new AuthCodeSession($email, $authCode, $generatedAt);
        $input = new VerifyEmailInput($email, $inputCode);

        $repository = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($session);
        $repository->shouldNotReceive('delete');
        $repository->shouldNotReceive('save');

        $factory = Mockery::mock(AuthCodeSessionFactoryInterface::class);
        $factory->shouldNotReceive('create');

        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $repository);
        $this->app->instance(AuthCodeSessionFactoryInterface::class, $factory);
        $useCase = $this->app->make(VerifyEmailInterface::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('認証コードが一致しません。');

        $useCase->process($input);
    }
}
