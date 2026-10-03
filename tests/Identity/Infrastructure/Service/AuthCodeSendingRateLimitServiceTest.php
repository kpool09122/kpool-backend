<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Illuminate\Support\Facades\Redis;
use Override;
use Source\Identity\Infrastructure\Service\AuthCodeSendingRateLimitService;
use Source\Shared\Domain\ValueObject\Email;
use Tests\TestCase;

class AuthCodeSendingRateLimitServiceTest extends TestCase
{
    /** @var list<string> */
    private array $keys = [];

    #[Override]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('database.redis.client', 'phpredis');
        $app['config']->set('database.redis.default', ['host' => getenv('REDIS_HOST') ?: 'redis', 'password' => null, 'port' => 6379, 'database' => 0]);
    }

    protected function tearDown(): void
    {
        if ($this->keys !== []) {
            Redis::del(...$this->keys);
        }
        parent::tearDown();
    }

    public function testCooldownCaseNormalizationHourlyLimitAndReset(): void
    {
        $service = new AuthCodeSendingRateLimitService();
        $email = new Email('User@Example.com');
        [$countKey, $cooldownKey] = $this->keysFor($email);

        $first = $service->reserve($email);
        $this->assertTrue($first->sendingAllowed);
        $this->assertSame(4, $first->remainingSends);
        $this->assertSame(60, $first->retryAfterSeconds);

        $suppressed = $service->reserve(new Email('user@example.com'));
        $this->assertFalse($suppressed->sendingAllowed);
        $this->assertSame(4, $suppressed->remainingSends);
        $this->assertContains($suppressed->retryAfterSeconds, [59, 60]);
        $this->assertSame('1', Redis::get($countKey));

        $otherEmail = new Email('other@example.com');
        $this->keysFor($otherEmail);
        $other = $service->reserve($otherEmail);
        $this->assertTrue($other->sendingAllowed);
        $this->assertSame(4, $other->remainingSends);

        for ($send = 2; $send <= 5; $send++) {
            Redis::del($cooldownKey);
            $status = $service->reserve($email);
            $this->assertTrue($status->sendingAllowed);
            $this->assertSame(5 - $send, $status->remainingSends);
        }
        $windowTtl = (int) Redis::ttl($countKey);
        $sixth = $service->reserve($email);
        $this->assertFalse($sixth->sendingAllowed);
        $this->assertSame(0, $sixth->remainingSends);
        $this->assertEqualsWithDelta($windowTtl, $sixth->retryAfterSeconds, 1);
        $this->assertSame('5', Redis::get($countKey));

        Redis::del($countKey, $cooldownKey);
        $reset = $service->reserve($email);
        $this->assertTrue($reset->sendingAllowed);
        $this->assertSame(4, $reset->remainingSends);
    }

    /** @return array{string, string} */
    private function keysFor(Email $email): array
    {
        $hash = hash('sha256', strtolower((string) $email));
        $keys = ['auth_code_email_sends:' . $hash, 'auth_code_email_cooldown:' . $hash];
        $this->keys = array_merge($this->keys, $keys);

        return $keys;
    }
}
