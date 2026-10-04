<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Mail\PasskeyRecoveryCodeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;
use Source\Identity\Application\Service\EmailSendingStatus;
use Source\Identity\Infrastructure\Service\AuthCodeSendingRateLimitService;
use Source\Identity\Infrastructure\Service\PasskeyRecoveryEmailVerificationService;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class EmailSendingWindowExpiryTest extends TestCase
{
    #[DataProvider('expiredWindowProvider')]
    public function testWindowExpiringBetweenCountAndTtlReadsDoesNotUseStaleCount(bool $passkeyRecovery, int $staleCount, int $cooldownTtl): void
    {
        Mail::fake();
        $hash = hash('sha256', 'user@example.com');
        $prefix = $passkeyRecovery ? 'passkey_recovery_email' : 'auth_code_email';
        $countKey = $prefix . '_sends:' . $hash;
        $cooldownKey = $prefix . '_cooldown:' . $hash;
        Redis::shouldReceive('get')->once()->with($countKey)->andReturn((string) $staleCount);
        Redis::shouldReceive('ttl')->once()->with($countKey)->andReturn(-2);
        Redis::shouldReceive('ttl')->once()->with($cooldownKey)->andReturn($cooldownTtl);

        if ($cooldownTtl > 0) {
            Redis::shouldReceive('incr')->never();
            Redis::shouldReceive('expire')->never();
            Redis::shouldReceive('setex')->never();
        } else {
            Redis::shouldReceive('incr')->once()->with($countKey)->andReturn(1);
            Redis::shouldReceive('expire')->once()->with($countKey, 3600)->andReturn(true);
            Redis::shouldReceive('setex')->once()->with($cooldownKey, 60, '1')->andReturn(true);
            if ($passkeyRecovery) {
                Redis::shouldReceive('setex')->once()->with('passkey_recovery_email:' . $hash, 600, Mockery::type('string'))->andReturn(true);
            }
        }

        $status = $this->send($passkeyRecovery);

        $this->assertSame($cooldownTtl <= 0, $status->sendingAllowed);
        $this->assertSame($cooldownTtl > 0 ? 5 : 4, $status->remainingSends);
        $this->assertSame($cooldownTtl > 0 ? $cooldownTtl : 60, $status->retryAfterSeconds);
        if ($passkeyRecovery && $cooldownTtl <= 0) {
            Mail::assertQueued(PasskeyRecoveryCodeMail::class, 1);
        } else {
            Mail::assertNothingOutgoing();
        }
    }

    /** @return array<string, array{bool, int, int}> */
    public static function expiredWindowProvider(): array
    {
        return [
            'auth exhausted window' => [false, 5, -2],
            'auth partially used window' => [false, 4, -2],
            'auth active cooldown' => [false, 5, 30],
            'passkey exhausted window' => [true, 5, -2],
            'passkey partially used window' => [true, 4, -2],
            'passkey active cooldown' => [true, 5, 30],
        ];
    }

    #[DataProvider('serviceProvider')]
    public function testExistingWindowWithoutExpiryIsStillRepaired(bool $passkeyRecovery): void
    {
        $prefix = $passkeyRecovery ? 'passkey_recovery_email' : 'auth_code_email';
        $countKey = $prefix . '_sends:' . hash('sha256', 'user@example.com');
        Redis::shouldReceive('get')->once()->with($countKey)->andReturn('5');
        Redis::shouldReceive('ttl')->once()->with($countKey)->andReturn(-1);
        Redis::shouldReceive('expire')->once()->with($countKey, 3600)->andReturn(true);
        Redis::shouldReceive('incr')->never();
        Redis::shouldReceive('setex')->never();

        $status = $this->send($passkeyRecovery);

        $this->assertFalse($status->sendingAllowed);
        $this->assertSame(0, $status->remainingSends);
        $this->assertSame(3600, $status->retryAfterSeconds);
    }

    /** @return array<string, array{bool}> */
    public static function serviceProvider(): array
    {
        return ['auth' => [false], 'passkey' => [true]];
    }

    private function send(bool $passkeyRecovery): EmailSendingStatus
    {
        $email = new Email('user@example.com');
        if ($passkeyRecovery) {
            return (new PasskeyRecoveryEmailVerificationService(new NullLogger()))->send(
                $email,
                new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000'),
                Language::JAPANESE,
            );
        }

        return (new AuthCodeSendingRateLimitService())->reserve($email);
    }
}
