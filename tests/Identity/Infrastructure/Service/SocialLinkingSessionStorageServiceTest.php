<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Mail\SocialLinkingCodeMail;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Identity\Domain\Exception\SocialLinkingSessionInvalidException;
use Source\Identity\Domain\Exception\SocialLinkingVerificationFailedException;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Infrastructure\Service\SocialLinkingSessionStorageService;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class SocialLinkingSessionStorageServiceTest extends TestCase
{
    /** @phpstan-ignore property.uninitialized */
    private Request $request;

    /** @phpstan-ignore property.uninitialized */
    private SocialLinkingSessionStorageService $socialLinkingSessionStorageService;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->request = Request::create('/');
        $session = new Store('test', new ArraySessionHandler(120));
        $session->start();
        $this->request->setLaravelSession($session);
        $this->socialLinkingSessionStorageService = new SocialLinkingSessionStorageService($this->request);
    }

    protected function tearDown(): void
    {
        Redis::flushdb();
        parent::tearDown();
    }

    #[Override]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('app.key', 'social-linking-test-key');
        $app['config']->set('database.redis.client', 'phpredis');
        $app['config']->set('database.redis.default', ['host' => getenv('REDIS_HOST') ?: 'redis', 'password' => null, 'port' => 6379, 'database' => 0]);
    }

    public function testPendingDataPreservesAuthenticatedConnectionAndTargetWithoutSendingAnEmail(): void
    {
        $this->issue();
        $pending = $this->socialLinkingSessionStorageService->requireValid();
        $this->assertSame('123e4567-e89b-72d3-a456-426614174001', (string) $pending->identityIdentifier);
        $this->assertSame('target@example.com', (string) $pending->email);
        $this->assertSame(SocialProvider::GOOGLE, $pending->connection->provider());
        $this->assertSame('authenticated-provider-user', $pending->connection->providerUserId());
        $this->assertSame('/settings?tab=login', $pending->returnTo);
        $this->assertEqualsWithDelta(time() + 600, $pending->expiresAt->getTimestamp(), 2);
        Mail::assertNothingOutgoing();
    }

    public function testDedicatedCodeIsHashedAndCanBeConsumedOnlyOnce(): void
    {
        $this->issue();
        $key = $this->key();
        $code = $this->sendCode();
        $raw = Redis::get($key);
        $this->assertIsString($raw);
        $this->assertStringNotContainsString('"' . $code . '"', $raw);
        $pending = $this->socialLinkingSessionStorageService->verifyAndConsume(new AuthCode($code));
        $this->assertSame('target@example.com', (string) $pending->email);
        $this->assertNull(Redis::get($key));
        $this->assertNull($this->request->session()->get('social_linking_pending'));
        $this->expectException(SocialLinkingSessionInvalidException::class);
        $this->socialLinkingSessionStorageService->verifyAndConsume(new AuthCode($code));
    }

    public function testCopyingPendingTokenToAnotherSessionCannotSendOrVerify(): void
    {
        $this->issue();
        $code = $this->sendCode();
        $originalId = $this->request->session()->getId();
        $this->request->session()->migrate();

        try {
            $this->socialLinkingSessionStorageService->sendCode(Language::JAPANESE);
            $this->fail('A different session must not send a code.');
        } catch (SocialLinkingSessionInvalidException) {
            Mail::assertQueued(SocialLinkingCodeMail::class, 1);
        }

        try {
            $this->socialLinkingSessionStorageService->verifyAndConsume(new AuthCode($code));
            $this->fail('A different session must not verify a code.');
        } catch (SocialLinkingSessionInvalidException) {
            $this->request->session()->setId($originalId);
            $this->assertSame('target@example.com', (string) $this->socialLinkingSessionStorageService->verifyAndConsume(new AuthCode($code))->email);
        }
    }

    public function testMissingLaravelSessionIsRejected(): void
    {
        $this->expectException(SocialLinkingSessionInvalidException::class);
        (new SocialLinkingSessionStorageService(Request::create('/')))->requireValid();
    }

    public function testMissingPendingTokenIsRejected(): void
    {
        $this->expectException(SocialLinkingSessionInvalidException::class);
        $this->socialLinkingSessionStorageService->requireValid();
    }

    public function testExpiredPendingIsRejectedEvenIfRedisStillContainsIt(): void
    {
        $this->issue();
        $this->updateData(['expires_at' => time() - 1]);
        $this->expectException(SocialLinkingSessionInvalidException::class);
        $this->socialLinkingSessionStorageService->requireValid();
    }

    public function testRecoveryPurposeCannotBeUsedForLinking(): void
    {
        $this->issue();
        $code = $this->sendCode();
        $this->updateData(['purpose' => 'passkey.recover']);
        $this->expectException(SocialLinkingSessionInvalidException::class);
        $this->socialLinkingSessionStorageService->verifyAndConsume(new AuthCode($code));
    }

    public function testMalformedPendingDataIsRejected(): void
    {
        $this->issue();
        Redis::setex($this->key(), 600, '{broken');
        $this->expectException(SocialLinkingSessionInvalidException::class);
        $this->socialLinkingSessionStorageService->requireValid();
    }

    public function testWrongCodeSpendsAttemptWithoutConsumingCorrectCode(): void
    {
        $this->issue();
        $code = $this->sendCode();

        try {
            $this->socialLinkingSessionStorageService->verifyAndConsume(new AuthCode($code === '000000' ? '000001' : '000000'));
            $this->fail('Wrong code must fail.');
        } catch (SocialLinkingVerificationFailedException) {
            $this->assertSame('target@example.com', (string) $this->socialLinkingSessionStorageService->verifyAndConsume(new AuthCode($code))->email);
        }
    }

    public function testFiveWrongAttemptsInvalidateThePendingSession(): void
    {
        $this->issue();
        $key = $this->key();
        $code = $this->sendCode();
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->socialLinkingSessionStorageService->verifyAndConsume(new AuthCode($code === '000000' ? '000001' : '000000'));
                $this->fail('Wrong code must fail.');
            } catch (SocialLinkingVerificationFailedException) {
                // Each incorrect code counts toward the attempt limit.
            }
        }
        $this->assertNull(Redis::get($key));
        $this->expectException(SocialLinkingSessionInvalidException::class);
        $this->socialLinkingSessionStorageService->verifyAndConsume(new AuthCode($code));
    }

    public function testCodeCannotBeVerifiedBeforeSending(): void
    {
        $this->issue();
        $this->expectException(SocialLinkingVerificationFailedException::class);
        $this->socialLinkingSessionStorageService->verifyAndConsume(new AuthCode('123456'));
    }

    public function testResendCooldownDoesNotChangeTheValidCode(): void
    {
        $this->issue();
        $code = $this->sendCode();
        $this->socialLinkingSessionStorageService->sendCode(Language::ENGLISH);
        Mail::assertQueued(SocialLinkingCodeMail::class, 1);
        $this->socialLinkingSessionStorageService->verifyAndConsume(new AuthCode($code));
    }

    public function testResendingPreservesAttemptsAndOriginalExpiryAndReplacesTheHash(): void
    {
        $this->issue();
        $this->sendCode();
        $expiry = $this->socialLinkingSessionStorageService->requireValid()->expiresAt;
        $this->updateData(['attempts' => 4, 'sent_at' => time() - 61, 'code_hash' => 'obsolete-hash']);
        $code = $this->sendCode();
        $this->assertEquals($expiry, $this->socialLinkingSessionStorageService->requireValid()->expiresAt);
        $data = $this->data();
        $this->assertSame(4, $data['attempts']);
        $this->assertNotSame('obsolete-hash', $data['code_hash']);
        $this->socialLinkingSessionStorageService->verifyAndConsume(new AuthCode($code));
    }

    public function testIdentitySendBudgetSurvivesReissuingPendingSessions(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->issue();
            $this->socialLinkingSessionStorageService->sendCode(Language::JAPANESE);
        }
        Mail::assertQueued(SocialLinkingCodeMail::class, 5);
    }

    public function testPendingSendBudgetStopsAtFiveEmails(): void
    {
        $this->issue();
        for ($i = 0; $i < 6; $i++) {
            $this->socialLinkingSessionStorageService->sendCode(Language::JAPANESE);
            $this->updateData(['sent_at' => time() - 61]);
        }
        Mail::assertQueued(SocialLinkingCodeMail::class, 5);
    }

    public function testReissueInvalidatesPreviousTokenAndCode(): void
    {
        $this->issue();
        $oldKey = $this->key();
        $this->sendCode();
        $this->issue();
        $this->assertNull(Redis::get($oldKey));
        $this->assertNotSame($oldKey, $this->key());
    }

    #[DataProvider('unsafeReturnToProvider')]
    public function testUnsafeReturnToCannotBePersisted(string $returnTo): void
    {
        $this->expectException(SocialLinkingSessionInvalidException::class);
        $this->issue($returnTo);
    }

    /** @return array<string, array{string}> */
    public static function unsafeReturnToProvider(): array
    {
        return [
            'external' => ['https://attacker.example/path'],
            'protocol relative' => ['//attacker.example/path'],
            'backslash' => ['/\\attacker.example/path'],
            'control character' => ["/\n/attacker.example"],
            'empty' => [''],
        ];
    }

    private function issue(string $returnTo = '/settings?tab=login'): void
    {
        $this->socialLinkingSessionStorageService->issue(
            new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174001'),
            new Email('target@example.com'),
            new SocialConnection(SocialProvider::GOOGLE, 'authenticated-provider-user'),
            $returnTo,
        );
    }

    private function sendCode(): string
    {
        $this->socialLinkingSessionStorageService->sendCode(Language::JAPANESE);
        $mail = Mail::queued(SocialLinkingCodeMail::class)->last();
        $this->assertInstanceOf(SocialLinkingCodeMail::class, $mail);
        $this->assertTrue($mail->hasTo('target@example.com'));
        $this->assertSame(Language::JAPANESE, $mail->language);

        return $mail->code;
    }

    private function key(): string
    {
        $token = $this->request->session()->get('social_linking_pending');
        $this->assertIsString($token);

        return 'social_linking_pending:' . $token;
    }

    /** @return array<array-key, mixed> */
    private function data(): array
    {
        $raw = Redis::get($this->key());
        $this->assertIsString($raw);
        $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);

        return $data;
    }

    /** @param array<array-key, mixed> $changes */
    private function updateData(array $changes): void
    {
        Redis::setex($this->key(), 600, json_encode(array_replace($this->data(), $changes), JSON_THROW_ON_ERROR));
    }
}
