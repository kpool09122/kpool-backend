<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\SendAuthCode;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Source\Identity\Application\Service\AuthCodeSendingRateLimitServiceInterface;
use Source\Identity\Application\Service\AuthCodeSessionStorageServiceInterface;
use Source\Identity\Application\Service\EmailSendingStatus;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCode;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCodeInput;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCodeOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Service\AuthCodeServiceInterface;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Identity\Domain\ValueObject\AuthCodeSession;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class SendAuthCodeTest extends TestCase
{
    #[DataProvider('identityProvider')]
    public function testAllowedRequestSendsTheAppropriateMessage(bool $exists): void
    {
        $email = new Email('user@example.com');
        $language = Language::KOREAN;
        $identity = $exists ? new Identity(new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000'), new IdentityName('user'), $email, $language, null, new DateTimeImmutable()) : null;
        /** @var IdentityRepositoryInterface&MockInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')->once()->with($email)->andReturn($identity);
        /** @var AuthCodeSendingRateLimitServiceInterface&MockInterface $limit */
        $limit = Mockery::mock(AuthCodeSendingRateLimitServiceInterface::class);
        $limit->shouldReceive('reserve')->once()->with($email)->andReturn(new EmailSendingStatus(true, 4, 60));
        /** @var AuthCodeSessionStorageServiceInterface&MockInterface $storage */
        $storage = Mockery::mock(AuthCodeSessionStorageServiceInterface::class);
        /** @var AuthCodeServiceInterface&MockInterface $service */
        $service = Mockery::mock(AuthCodeServiceInterface::class);
        if ($exists) {
            $service->shouldReceive('notifyConflict')->once()->with($email, $language);
            $service->shouldNotReceive('generateCode', 'send');
            $storage->shouldNotReceive('store');
        } else {
            $code = new AuthCode('123456');
            $service->shouldReceive('generateCode')->once()->with($email)->andReturn($code);
            $storage->shouldReceive('store')->once()->with(Mockery::on(static fn (AuthCodeSession $session): bool => $session->email() === $email && $session->authCode() === $code));
            $service->shouldReceive('send')->once()->with($email, $language, Mockery::type(AuthCodeSession::class));
        }
        $output = new SendAuthCodeOutput();

        (new SendAuthCode($service, $identityRepository, $storage, $limit))->process(new SendAuthCodeInput($email, $language), $output);

        $this->assertSame(['accepted' => true, 'remainingSends' => 4, 'retryAfterSeconds' => 60], $output->toArray());
    }

    public function testSuppressedRequestDoesNotReadIdentityOrChangeSessionOrSendMail(): void
    {
        $email = new Email('user@example.com');
        /** @var AuthCodeSendingRateLimitServiceInterface&MockInterface $limit */
        $limit = Mockery::mock(AuthCodeSendingRateLimitServiceInterface::class);
        $limit->shouldReceive('reserve')->once()->with($email)->andReturn(new EmailSendingStatus(false, 4, 59));
        /** @var IdentityRepositoryInterface&MockInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldNotReceive('findByEmail');
        /** @var AuthCodeSessionStorageServiceInterface&MockInterface $storage */
        $storage = Mockery::mock(AuthCodeSessionStorageServiceInterface::class);
        $storage->shouldNotReceive('store');
        /** @var AuthCodeServiceInterface&MockInterface $service */
        $service = Mockery::mock(AuthCodeServiceInterface::class);
        $service->shouldNotReceive('generateCode', 'send', 'notifyConflict');
        $output = new SendAuthCodeOutput();

        (new SendAuthCode($service, $identityRepository, $storage, $limit))->process(new SendAuthCodeInput($email, Language::JAPANESE), $output);

        $this->assertSame(['accepted' => true, 'remainingSends' => 4, 'retryAfterSeconds' => 59], $output->toArray());
    }

    public function testMailFailureKeepsTheReservedSlotAndRetryIsSuppressed(): void
    {
        $email = new Email('user@example.com');
        $language = Language::JAPANESE;
        $code = new AuthCode('123456');
        /** @var AuthCodeSendingRateLimitServiceInterface&MockInterface $limit */
        $limit = Mockery::mock(AuthCodeSendingRateLimitServiceInterface::class);
        $limit->shouldReceive('reserve')->twice()->with($email)->andReturn(
            new EmailSendingStatus(true, 4, 60),
            new EmailSendingStatus(false, 4, 59),
        );
        /** @var IdentityRepositoryInterface&MockInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')->once()->with($email)->andReturnNull();
        /** @var AuthCodeSessionStorageServiceInterface&MockInterface $storage */
        $storage = Mockery::mock(AuthCodeSessionStorageServiceInterface::class);
        $storage->shouldReceive('store')->once();
        /** @var AuthCodeServiceInterface&MockInterface $service */
        $service = Mockery::mock(AuthCodeServiceInterface::class);
        $service->shouldReceive('generateCode')->once()->with($email)->andReturn($code);
        $service->shouldReceive('send')->once()->andThrow(new RuntimeException('Mail delivery failed'));
        $useCase = new SendAuthCode($service, $identityRepository, $storage, $limit);

        try {
            $useCase->process(new SendAuthCodeInput($email, $language), new SendAuthCodeOutput());
            $this->fail('The delivery failure must propagate to the job or HTTP boundary.');
        } catch (RuntimeException) {
            $retryOutput = new SendAuthCodeOutput();
            $useCase->process(new SendAuthCodeInput($email, $language), $retryOutput);
            $this->assertSame(['accepted' => true, 'remainingSends' => 4, 'retryAfterSeconds' => 59], $retryOutput->toArray());
        }
    }

    /** @return array<array{bool}> */
    public static function identityProvider(): array
    {
        return [[true], [false]];
    }
}
