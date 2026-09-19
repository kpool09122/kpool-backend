<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Repository;

use Illuminate\Support\Facades\Redis;
use Source\Identity\Domain\Entity\PasskeyChallengeSession;
use Source\Identity\Domain\Exception\ChallengeSessionNotFoundException;
use Source\Identity\Domain\Repository\PasskeyChallengeSessionRepositoryInterface;
use Source\Identity\Infrastructure\Repository\PasskeyChallengeSessionRepository;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PasskeyChallengeSessionRepositoryTest extends TestCase
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

    public function testRepositoryBinding(): void
    {
        $this->assertInstanceOf(
            PasskeyChallengeSessionRepository::class,
            $this->app->make(PasskeyChallengeSessionRepositoryInterface::class),
        );
    }

    public function testSavedChallengeCanBeConsumedOnlyOnce(): void
    {
        $repository = $this->repository();
        $session = $this->signupSession();
        $repository->save($session);

        $consumed = $repository->consume($session->identifier(), PasskeyChallengeSession::PURPOSE_SIGNUP);

        $this->assertSame($session->purpose(), $consumed->purpose());
        $this->assertSame((string) $session->identityIdentifier(), (string) $consumed->identityIdentifier());
        $this->assertSame($session->signupData(), $consumed->signupData());
        $this->expectException(ChallengeSessionNotFoundException::class);
        $repository->consume($session->identifier(), PasskeyChallengeSession::PURPOSE_SIGNUP);
    }

    public function testWrongPurposeIsRejectedAndConsumesChallenge(): void
    {
        $repository = $this->repository();
        $session = $this->signupSession();
        $repository->save($session);

        $this->assertChallengeRejected(static function () use ($repository, $session): void {
            $repository->consume($session->identifier(), PasskeyChallengeSession::PURPOSE_LOGIN);
        });

        $this->expectException(ChallengeSessionNotFoundException::class);
        $repository->consume($session->identifier(), PasskeyChallengeSession::PURPOSE_SIGNUP);
    }

    public function testMissingOrExpiredChallengeIsRejected(): void
    {
        $this->expectException(ChallengeSessionNotFoundException::class);
        $this->repository()->consume(StrTestHelper::generateUuid(), PasskeyChallengeSession::PURPOSE_LOGIN);
    }

    private function repository(): PasskeyChallengeSessionRepositoryInterface
    {
        return $this->app->make(PasskeyChallengeSessionRepositoryInterface::class);
    }

    /** @param callable(): void $operation */
    private function assertChallengeRejected(callable $operation): void
    {
        try {
            $operation();
        } catch (ChallengeSessionNotFoundException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('不正なチャレンジが受理されました');
    }

    private function signupSession(): PasskeyChallengeSession
    {
        return new PasskeyChallengeSession(
            StrTestHelper::generateUuid(),
            PasskeyChallengeSession::PURPOSE_SIGNUP,
            '{"challenge":"value"}',
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            ['email' => 'user@example.com'],
        );
    }
}
