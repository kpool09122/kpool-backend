<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Redis;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\WebAuthn\AdditionChallenge;
use Source\Identity\Application\Service\WebAuthn\AuthenticationChallenge;
use Source\Identity\Application\Service\WebAuthn\RegistrationChallenge;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Domain\Exception\ChallengeSessionIdentityMismatchException;
use Source\Identity\Domain\Exception\ChallengeSessionNotFoundException;
use Source\Identity\Domain\Exception\ChallengeSessionPurposeMismatchException;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\PasskeyUserHandle;
use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Identity\Infrastructure\Service\ChallengeSessionStorageService;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class ChallengeSessionStorageServiceTest extends TestCase
{
    private const string CHALLENGE = 'MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY';

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
    public function testItIsBoundAndConsumesARegistrationChallengeExactlyOnce(): void
    {
        $service = $this->app->make(ChallengeSessionStorageServiceInterface::class);
        $this->assertInstanceOf(ChallengeSessionStorageService::class, $service);

        $key = new ChallengeSessionKey('123e4567-e89b-72d3-a456-426614174010');
        $challenge = new RegistrationChallenge(
            $key,
            new WebAuthnChallenge(self::CHALLENGE),
            new WebAuthnOptions('{"publicKey":"options"}'),
            new DateTimeImmutable('+5 minutes'),
            new PasskeyUserHandle('123e4567-e89b-72d3-a456-426614174001'),
            new Email('passkey@example.com'),
            new SignupSession(AccountType::INDIVIDUAL),
        );

        $service->storeRegistration($challenge);
        $consumed = $service->consumeRegistration($key);

        $this->assertSame((string) $challenge->challenge, (string) $consumed->challenge);
        $this->assertSame('{"publicKey":"options"}', $consumed->options->json());
        $this->assertSame('123e4567-e89b-72d3-a456-426614174001', (string) $consumed->userHandle);
        $this->assertSame('passkey@example.com', (string) $consumed->email);
        $this->assertSame(AccountType::INDIVIDUAL, $consumed->signupSession->accountType());

        $this->expectException(ChallengeSessionNotFoundException::class);
        $service->consumeRegistration($key);
    }

    public function testItStoresAndConsumesAnAuthenticationChallenge(): void
    {
        $service = $this->app->make(ChallengeSessionStorageServiceInterface::class);
        $key = new ChallengeSessionKey('123e4567-e89b-72d3-a456-426614174011');
        $challenge = new AuthenticationChallenge(
            $key,
            new WebAuthnChallenge(self::CHALLENGE),
            new WebAuthnOptions('{"publicKey":"authentication"}'),
            new DateTimeImmutable('+5 minutes'),
        );

        $service->storeAuthentication($challenge);
        $consumed = $service->consumeAuthentication($key);

        $this->assertSame((string) $challenge->challenge, (string) $consumed->challenge);
        $this->assertSame('{"publicKey":"authentication"}', $consumed->options->json());
    }

    public function testItStoresAndConsumesAnAdditionChallengeForTheExpectedIdentity(): void
    {
        $service = $this->app->make(ChallengeSessionStorageServiceInterface::class);
        $key = new ChallengeSessionKey('123e4567-e89b-72d3-a456-426614174012');
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        $challenge = new AdditionChallenge(
            $key,
            new WebAuthnChallenge(self::CHALLENGE),
            new WebAuthnOptions('{"publicKey":"addition"}'),
            new DateTimeImmutable('+5 minutes'),
            $identityIdentifier,
        );

        $service->storeAddition($challenge);
        $consumed = $service->consumeAddition($key, $identityIdentifier);

        $this->assertSame((string) $identityIdentifier, (string) $consumed->identityIdentifier);
    }

    public function testPurposeMismatchConsumesTheChallenge(): void
    {
        $service = $this->app->make(ChallengeSessionStorageServiceInterface::class);
        $key = new ChallengeSessionKey('123e4567-e89b-72d3-a456-426614174013');
        $service->storeAuthentication(new AuthenticationChallenge(
            $key,
            new WebAuthnChallenge(self::CHALLENGE),
            new WebAuthnOptions('{"publicKey":"options"}'),
            new DateTimeImmutable('+5 minutes'),
        ));

        try {
            $service->consumeRegistration($key);
            $this->fail('Purpose mismatch was not rejected.');
        } catch (ChallengeSessionPurposeMismatchException) {
            $this->expectException(ChallengeSessionNotFoundException::class);
            $service->consumeAuthentication($key);
        }
    }

    public function testIdentityMismatchIsRejectedAndConsumed(): void
    {
        $service = $this->app->make(ChallengeSessionStorageServiceInterface::class);
        $key = new ChallengeSessionKey('123e4567-e89b-72d3-a456-426614174014');
        $service->storeAddition(new AdditionChallenge(
            $key,
            new WebAuthnChallenge(self::CHALLENGE),
            new WebAuthnOptions('{"publicKey":"options"}'),
            new DateTimeImmutable('+5 minutes'),
            new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000'),
        ));

        $this->expectException(ChallengeSessionIdentityMismatchException::class);
        $service->consumeAddition(
            $key,
            new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174099'),
        );
    }

    public function testExpiredChallengeCannotBeStored(): void
    {
        $service = $this->app->make(ChallengeSessionStorageServiceInterface::class);

        $this->expectException(ChallengeSessionNotFoundException::class);
        $service->storeAuthentication(new AuthenticationChallenge(
            new ChallengeSessionKey('123e4567-e89b-72d3-a456-426614174015'),
            new WebAuthnChallenge(self::CHALLENGE),
            new WebAuthnOptions('{"publicKey":"options"}'),
            new DateTimeImmutable('-1 second'),
        ));
    }
}
