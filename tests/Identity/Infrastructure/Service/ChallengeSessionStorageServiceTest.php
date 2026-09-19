<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Redis;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Domain\Entity\ChallengeSession;
use Source\Identity\Domain\Exception\ChallengeSessionIdentityMismatchException;
use Source\Identity\Domain\Exception\ChallengeSessionNotFoundException;
use Source\Identity\Domain\Exception\ChallengeSessionPurposeMismatchException;
use Source\Identity\Domain\ValueObject\ChallengePurpose;
use Source\Identity\Domain\ValueObject\ChallengeSessionIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyRegistrationContext;
use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Identity\Infrastructure\Service\ChallengeSessionStorageService;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class ChallengeSessionStorageServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Redis::flushdb();
        parent::tearDown();
    }

    #[\Override]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('database.redis.client', 'phpredis');
        $app['config']->set('database.redis.default', [
            'host' => getenv('REDIS_HOST') ?: 'redis',
            'password' => null,
            'port' => 6379,
            'database' => 0,
        ]);
    }

    /** @throws BindingResolutionException */
    public function testItIsBoundAndConsumesARegistrationSessionExactlyOnce(): void
    {
        $service = $this->app->make(ChallengeSessionStorageServiceInterface::class);
        $this->assertInstanceOf(ChallengeSessionStorageService::class, $service);

        $identifier = new ChallengeSessionIdentifier('123e4567-e89b-72d3-a456-426614174010');
        $session = new ChallengeSession(
            $identifier,
            new WebAuthnChallenge('MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY'),
            ChallengePurpose::REGISTRATION,
            '{"publicKey":"options"}',
            new DateTimeImmutable('+5 minutes'),
            registrationContext: new PasskeyRegistrationContext(
                new Email('passkey@example.com'),
                new SignupSession(AccountType::INDIVIDUAL),
            ),
        );

        $service->store($session);
        $consumed = $service->consume($identifier, ChallengePurpose::REGISTRATION);

        $this->assertSame((string) $session->challenge(), (string) $consumed->challenge());
        $this->assertSame('passkey@example.com', (string) $consumed->registrationContext()?->email());
        $this->assertSame(AccountType::INDIVIDUAL, $consumed->registrationContext()?->signupSession()->accountType());

        $this->expectException(ChallengeSessionNotFoundException::class);
        $service->consume($identifier, ChallengePurpose::REGISTRATION);
    }

    public function testPurposeMismatchConsumesTheSession(): void
    {
        $service = $this->app->make(ChallengeSessionStorageServiceInterface::class);
        $identifier = new ChallengeSessionIdentifier('123e4567-e89b-72d3-a456-426614174011');
        $service->store(new ChallengeSession(
            $identifier,
            new WebAuthnChallenge('MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY'),
            ChallengePurpose::AUTHENTICATION,
            '{"publicKey":"options"}',
            new DateTimeImmutable('+5 minutes'),
        ));

        try {
            $service->consume($identifier, ChallengePurpose::REGISTRATION);
            $this->fail('Purpose mismatch was not rejected.');
        } catch (ChallengeSessionPurposeMismatchException) {
            $this->expectException(ChallengeSessionNotFoundException::class);
            $service->consume($identifier, ChallengePurpose::AUTHENTICATION);
        }
    }

    public function testIdentityMismatchIsRejectedAndConsumed(): void
    {
        $service = $this->app->make(ChallengeSessionStorageServiceInterface::class);
        $identifier = new ChallengeSessionIdentifier('123e4567-e89b-72d3-a456-426614174012');
        $service->store(new ChallengeSession(
            $identifier,
            new WebAuthnChallenge('MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY'),
            ChallengePurpose::ADDITION,
            '{"publicKey":"options"}',
            new DateTimeImmutable('+5 minutes'),
            identityIdentifier: new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000'),
        ));

        $this->expectException(ChallengeSessionIdentityMismatchException::class);
        $service->consume(
            $identifier,
            ChallengePurpose::ADDITION,
            new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174099'),
        );
    }

    public function testExpiredSessionCannotBeStored(): void
    {
        $service = $this->app->make(ChallengeSessionStorageServiceInterface::class);

        $this->expectException(ChallengeSessionNotFoundException::class);
        $service->store(new ChallengeSession(
            new ChallengeSessionIdentifier('123e4567-e89b-72d3-a456-426614174013'),
            new WebAuthnChallenge('MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY'),
            ChallengePurpose::AUTHENTICATION,
            '{"publicKey":"options"}',
            new DateTimeImmutable('-1 second'),
        ));
    }
}
