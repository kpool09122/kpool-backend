<?php

declare(strict_types=1);

namespace Tests\Application\Http\Action\Identity\SocialLinking;

use Application\Mail\SocialLinkingCodeMail;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Application\Service\SocialLinking\SocialLinkingSessionStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail\SendSocialLinkingEmailInterface;
use Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail\VerifySocialLinkingEmailInterface;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\Repository\SignupSessionRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Domain\ValueObject\SocialProfile;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class SocialLinkingFlowTest extends TestCase
{
    private ?string $identityRateLimitKey = null;

    protected function tearDown(): void
    {
        if ($this->identityRateLimitKey !== null) {
            Redis::del($this->identityRateLimitKey);
        }
        parent::tearDown();
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('database.redis.client', 'phpredis');
        $connection = ['host' => getenv('REDIS_HOST') ?: 'redis', 'password' => null, 'port' => 6379, 'database' => 0];
        $app['config']->set('database.redis.default', $connection);
        $app['config']->set('database.redis.cache', $connection);
    }

    protected function defineRoutes($router): void
    {
        $router->middleware(StartSession::class)->group(dirname(__DIR__, 6) . '/routes/identity_api.php');
    }

    #[DataProvider('passkeyPresence')]
    public function testCallbackEmailAndVerificationLinkOnceBeforeLogin(bool $hasPasskey): void
    {
        Mail::fake();
        $this->withCredentials();
        config(['app.frontend_url' => 'http://localhost:3000', 'session.driver' => 'array']);
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $this->identityRateLimitKey = 'social_linking_email_sends:' . hash('sha256', (string) $identityIdentifier);
        $providerUserId = 'google-' . StrTestHelper::generateUuid();
        $email = new Email('link-' . StrTestHelper::generateUuid() . '@example.com');
        CreateIdentity::create($identityIdentifier, ['email' => (string) $email]);
        if ($hasPasskey) {
            $this->createPasskey($identityIdentifier);
        }

        $oauthState = Mockery::mock(OAuthStateRepositoryInterface::class);
        $oauthState->shouldReceive('consume')->once();
        $this->app()->instance(OAuthStateRepositoryInterface::class, $oauthState);
        $signupSessions = Mockery::mock(SignupSessionRepositoryInterface::class);
        $signupSessions->shouldReceive('find')->once()->andReturnNull();
        $this->app()->instance(SignupSessionRepositoryInterface::class, $signupSessions);
        $oauth = Mockery::mock(SocialOAuthServiceInterface::class);
        $oauth->shouldReceive('fetchProfile')->once()->andReturn(new SocialProfile(
            SocialProvider::GOOGLE,
            $providerUserId,
            $email,
            'Existing owner',
        ));
        $this->app()->instance(SocialOAuthServiceInterface::class, $oauth);
        $identities = $this->app()->make(IdentityRepositoryInterface::class);
        $loginCount = 0;
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('login')->once()->andReturnUsing(function (Identity $identity) use (&$loginCount, $identities, $identityIdentifier, $providerUserId): Identity {
            $saved = $identities->findBySocialConnection(SocialProvider::GOOGLE, $providerUserId);
            $this->assertNotNull($saved, 'The connection must be saved before login.');
            $this->assertSame((string) $identityIdentifier, (string) $saved->identityIdentifier());
            $this->assertSame((string) $identityIdentifier, (string) $identity->identityIdentifier());
            ++$loginCount;

            return $identity;
        });
        $this->app()->instance(AuthServiceInterface::class, $auth);

        $callback = $this->get('/auth/social/google/callback?code=oauth-code&state=oauth-state');
        $callback->assertRedirect('http://localhost:3000/auth/social/link');
        $this->assertSame(0, $loginCount);
        $this->assertNull($identities->findBySocialConnection(SocialProvider::GOOGLE, $providerUserId));
        Mail::assertNothingQueued();

        $cookieName = config('session.cookie');
        $this->assertIsString($cookieName);
        $sessionCookie = collect($callback->headers->getCookies())->first(fn (Cookie $cookie): bool => $cookie->getName() === $cookieName);
        $this->assertInstanceOf(Cookie::class, $sessionCookie);
        $sessionCookieValue = $sessionCookie->getValue();
        $this->assertIsString($sessionCookieValue);
        $this->withUnencryptedCookie($cookieName, $sessionCookieValue);
        $this->refreshPendingStorage();
        $this->getJson('/auth/social/link')->assertOk()
            ->assertJsonPath('provider', 'google')->assertJsonPath('email', (string) $email);
        $this->refreshPendingStorage();
        $this->postJson('/auth/social/link/email', ['email' => 'attacker@example.com'])->assertOk()->assertJsonPath('accepted', true);
        $this->assertSame(0, $loginCount);
        $this->assertNull($identities->findBySocialConnection(SocialProvider::GOOGLE, $providerUserId));
        $code = null;
        Mail::assertQueued(SocialLinkingCodeMail::class, function (SocialLinkingCodeMail $mail) use ($email, &$code): bool {
            $code = $mail->code;

            return $mail->hasTo((string) $email);
        });
        $this->assertIsString($code);

        // Possessing the emailed code does not authorize a different browser session.
        $this->withUnencryptedCookie($cookieName, str_repeat('a', 40));
        $this->refreshPendingStorage();
        $this->getJson('/auth/social/link')->assertUnprocessable();
        $this->refreshPendingStorage();
        $this->postJson('/auth/social/link/email/verification', ['authCode' => $code])->assertUnprocessable();
        $this->assertSame(0, $loginCount);
        $this->withUnencryptedCookie($cookieName, $sessionCookieValue);

        $this->refreshPendingStorage();
        $this->postJson('/auth/social/link/email/verification', [
            'authCode' => $code,
            'returnTo' => 'https://evil.example',
            'providerUserId' => 'attacker',
        ])->assertOk()->assertExactJson(['redirectUrl' => '/auth/callback']);
        $this->assertSame(1, $loginCount);
        $this->refreshPendingStorage();
        $this->postJson('/auth/social/link/email/verification', ['authCode' => $code])->assertUnprocessable();
        $this->assertSame(1, $loginCount);
        $this->refreshPendingStorage();
        $this->getJson('/auth/social/link')->assertUnprocessable();
        $this->assertCount($hasPasskey ? 1 : 0, $this->app()->make(PasskeyCredentialRepositoryInterface::class)->findByIdentityIdentifier($identityIdentifier));
    }

    /** @return array<string, array{bool}> */
    public static function passkeyPresence(): array
    {
        return ['without passkey' => [false], 'with passkey' => [true]];
    }

    private function createPasskey(IdentityIdentifier $identityIdentifier): void
    {
        $userIdentifier = new PasskeyUserIdentifier(StrTestHelper::generateUuid());
        $user = new PasskeyUser($userIdentifier, null);
        $user->linkToIdentity($identityIdentifier);
        $this->app()->make(PasskeyUserRepositoryInterface::class)->save($user);
        $this->app()->make(PasskeyCredentialRepositoryInterface::class)->save(new PasskeyCredential(
            new PasskeyCredentialIdentifier(StrTestHelper::generateUuid()),
            $userIdentifier,
            new WebAuthnCredentialId('cGFzc2tleS1saW5raW5n'),
            new CredentialSource('{"credential":"source"}'),
            0,
            false,
            false,
            ['internal'],
            new PasskeyDisplayName('Existing passkey'),
            null,
        ));
    }

    private function refreshPendingStorage(): void
    {
        // Testbench reuses the container across HTTP requests; production resolves this per request.
        foreach ([SocialLinkingSessionStorageServiceInterface::class, SendSocialLinkingEmailInterface::class, VerifySocialLinkingEmailInterface::class] as $service) {
            $this->app()->forgetInstance($service);
        }
    }
}
