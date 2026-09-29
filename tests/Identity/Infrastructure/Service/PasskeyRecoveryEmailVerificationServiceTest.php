<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Mail\PasskeyRecoveryCodeMail;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Mockery\MockInterface;
use Override;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Source\Identity\Domain\Exception\PasskeyRecoveryVerificationFailedException;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Identity\Infrastructure\Service\PasskeyRecoveryEmailVerificationService;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class PasskeyRecoveryEmailVerificationServiceTest extends TestCase
{
    private const string EMAIL = 'user@example.com';

    protected function tearDown(): void
    {
        $hash = hash('sha256', self::EMAIL);
        $key = 'passkey_recovery_email:' . $hash;
        Redis::del($key, $key . ':attempts', 'passkey_recovery_email_sends:' . $hash);
        Cache::store('redis')->forget('passkey_recovery_email_cooldown:' . $hash);
        parent::tearDown();
    }

    #[Override]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('app.key', 'test-passkey-recovery-key');
        $app['config']->set('database.redis.client', 'phpredis');
        $app['config']->set('database.redis.default', ['host' => getenv('REDIS_HOST') ?: 'redis', 'password' => null, 'port' => 6379, 'database' => 0]);
    }

    public function testAValidCodeCanBeConsumedOnlyOnce(): void
    {
        $email = new Email(self::EMAIL);
        $code = new AuthCode('123456');
        $this->storeVerification($email, $code, 0);
        $service = new PasskeyRecoveryEmailVerificationService(new NullLogger());
        $this->assertSame('123e4567-e89b-72d3-a456-426614174000', (string) $service->verify($email, $code));
        $this->expectException(PasskeyRecoveryVerificationFailedException::class);
        $service->verify($email, $code);
    }

    public function testExpiredCodeIsRejected(): void
    {
        $this->expectException(PasskeyRecoveryVerificationFailedException::class);
        (new PasskeyRecoveryEmailVerificationService(new NullLogger()))->verify(new Email(self::EMAIL), new AuthCode('123456'));
    }

    public function testAttemptLimitDeletesTheVerification(): void
    {
        $email = new Email(self::EMAIL);
        $this->storeVerification($email, new AuthCode('123456'), 5);
        $key = $this->verificationKey($email);

        try {
            (new PasskeyRecoveryEmailVerificationService(new NullLogger()))->verify($email, new AuthCode('654321'));
            $this->fail('Attempt limit was not enforced.');
        } catch (PasskeyRecoveryVerificationFailedException) {
            $this->assertNull(Redis::get($key));
        }
    }

    public function testHourlySendLimitStopsAfterFiveEmails(): void
    {
        Mail::fake();
        $email = new Email(self::EMAIL);
        $service = new PasskeyRecoveryEmailVerificationService(new NullLogger());
        $hash = hash('sha256', self::EMAIL);
        for ($i = 0; $i < 6; $i++) {
            $service->send($email, new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000'), Language::JAPANESE);
            Cache::store('redis')->forget('passkey_recovery_email_cooldown:' . $hash);
        }
        Mail::assertQueued(PasskeyRecoveryCodeMail::class, 5);
        Mail::assertNothingSent();
    }

    public function testCodeUsesDefaultConnection(): void
    {
        config(['queue.default' => 'sync']);
        Mail::fake();
        $email = new Email(self::EMAIL);
        $identityId = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        $service = new PasskeyRecoveryEmailVerificationService(new NullLogger());
        $service->send($email, $identityId, Language::JAPANESE);

        Mail::assertQueued(PasskeyRecoveryCodeMail::class, function (PasskeyRecoveryCodeMail $mail) use ($service, $email, $identityId): bool {
            $this->assertEquals($identityId, $service->verify($email, new AuthCode($mail->code)));

            return $mail->hasTo(self::EMAIL) && $mail->connection === null && $mail->queue === null && $mail->language === Language::JAPANESE;
        });
        Mail::assertNothingSent();
    }

    public function testUnknownEmailDoesNotQueueMail(): void
    {
        Mail::fake();
        (new PasskeyRecoveryEmailVerificationService(new NullLogger()))->send(new Email(self::EMAIL), null, Language::JAPANESE);
        Mail::assertNothingOutgoing();
    }

    public function testQueueFailureIsLoggedWithoutFailingTheRequest(): void
    {
        $failure = new RuntimeException('Queue unavailable');
        /** @var LoggerInterface&MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once()->with('Failed to queue passkey recovery code email.', ['exception' => $failure]);
        $pending = Mockery::mock(PendingMail::class);
        Mail::shouldReceive('to')->once()->with(self::EMAIL)->andReturn($pending);
        $pending->shouldReceive('queue')->once()->andThrow($failure);

        (new PasskeyRecoveryEmailVerificationService($logger))->send(
            new Email(self::EMAIL),
            new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000'),
            Language::JAPANESE,
        );
    }

    private function storeVerification(Email $email, AuthCode $code, int $attempts): void
    {
        $key = $this->verificationKey($email);
        $appKey = config('app.key');
        $this->assertIsString($appKey);
        Redis::setex($key, 900, json_encode([
            'identity_id' => '123e4567-e89b-72d3-a456-426614174000',
            'code_hash' => hash_hmac('sha256', (string) $code, $appKey),
        ], JSON_THROW_ON_ERROR));
        if ($attempts > 0) {
            Redis::setex($key . ':attempts', 900, (string) $attempts);
        }
    }

    private function verificationKey(Email $email): string
    {
        return 'passkey_recovery_email:' . hash('sha256', strtolower((string) $email));
    }
}
