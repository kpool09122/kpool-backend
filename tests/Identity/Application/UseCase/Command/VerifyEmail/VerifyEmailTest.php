<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\VerifyEmail;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Identity\Application\Service\AuthCodeSessionStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\VerifyEmail\VerifyEmail;
use Source\Identity\Application\UseCase\Command\VerifyEmail\VerifyEmailInput;
use Source\Identity\Application\UseCase\Command\VerifyEmail\VerifyEmailInterface;
use Source\Identity\Application\UseCase\Command\VerifyEmail\VerifyEmailOutput;
use Source\Identity\Domain\Exception\AuthCodeExpiredException;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Identity\Domain\Exception\InvalidAuthCodeException;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Identity\Domain\ValueObject\AuthCodeSession;
use Source\Shared\Domain\ValueObject\Email;
use Tests\TestCase;
use Throwable;

class VerifyEmailTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $this->app()->instance(AuthCodeSessionStorageServiceInterface::class, Mockery::mock(AuthCodeSessionStorageServiceInterface::class));

        $this->assertInstanceOf(VerifyEmail::class, $this->app()->make(VerifyEmailInterface::class));
    }

    /**
     * @throws BindingResolutionException
     * @throws AuthCodeSessionNotFoundException
     */
    public function testProcess(): void
    {
        $email = new Email('user@example.com');
        $authCode = new AuthCode('123456');
        $existingSession = new AuthCodeSession($email, $authCode, new DateTimeImmutable('-5 minutes'));
        $before = new DateTimeImmutable('now');

        $storageService = Mockery::mock(AuthCodeSessionStorageServiceInterface::class);
        $storageService->shouldReceive('findByEmail')->once()->with($email)->andReturn($existingSession);
        $storageService->shouldReceive('delete')->once()->with($email);
        $storageService->shouldReceive('store')
            ->once()
            ->with(Mockery::on(function (AuthCodeSession $session) use ($email, $authCode, $before): bool {
                $this->assertSame($email, $session->email());
                $this->assertSame($authCode, $session->authCode());
                $this->assertGreaterThanOrEqual($before->getTimestamp(), $session->generatedAt()->getTimestamp());
                $this->assertLessThanOrEqual((new DateTimeImmutable('now'))->getTimestamp(), $session->generatedAt()->getTimestamp());
                $this->assertSame($session->generatedAt(), $session->verifiedAt());
                $this->assertSame($session->generatedAt()->modify('+15 minutes')->getTimestamp(), $session->expiresAt()->getTimestamp());

                return true;
            }));

        $this->app()->instance(AuthCodeSessionStorageServiceInterface::class, $storageService);
        $output = new VerifyEmailOutput();
        $this->app()->make(VerifyEmailInterface::class)->process(new VerifyEmailInput($email, $authCode), $output);

        self::assertTrue(array_key_exists('email', $output->toArray()));
        $this->assertSame((string) $email, $output->toArray()['email']);
        $this->assertNotNull($output->toArray()['verifiedAt']);
    }

    public function testProcessWhenSessionNotFound(): void
    {
        $this->assertFailureDoesNotPersist(
            null,
            AuthCodeSessionNotFoundException::class,
            new AuthCode('123456'),
        );
    }

    public function testProcessWhenSessionExpired(): void
    {
        $email = new Email('user@example.com');
        $authCode = new AuthCode('123456');
        $this->assertFailureDoesNotPersist(
            new AuthCodeSession($email, $authCode, new DateTimeImmutable('-20 minutes')),
            AuthCodeExpiredException::class,
            $authCode,
        );
    }

    public function testProcessWhenAuthCodeMismatch(): void
    {
        $email = new Email('user@example.com');
        $this->assertFailureDoesNotPersist(
            new AuthCodeSession($email, new AuthCode('123456'), new DateTimeImmutable('-5 minutes')),
            InvalidAuthCodeException::class,
            new AuthCode('654321'),
        );
    }

    /**
     * @param class-string<Throwable> $expectedException
     */
    private function assertFailureDoesNotPersist(
        ?AuthCodeSession $session,
        string $expectedException,
        AuthCode $inputCode,
    ): void {
        $email = new Email('user@example.com');
        $storageService = Mockery::mock(AuthCodeSessionStorageServiceInterface::class);
        $storageService->shouldReceive('findByEmail')->once()->with($email)->andReturn($session);
        $storageService->shouldNotReceive('delete');
        $storageService->shouldNotReceive('store');
        $this->app()->instance(AuthCodeSessionStorageServiceInterface::class, $storageService);

        $this->expectException($expectedException);

        $this->app()->make(VerifyEmailInterface::class)->process(
            new VerifyEmailInput($email, $inputCode),
            new VerifyEmailOutput(),
        );
    }
}
