<?php

declare(strict_types=1);

namespace Tests\Http\Action\Identity;

use Application\Http\Exceptions\Handler;
use Application\Http\Middleware\EnsureAccountActive;
use Application\Http\Middleware\EnsureAuthenticated;
use Application\Http\Middleware\PreventRequestForgery;
use Application\Http\Middleware\ResolveActorContext;
use Application\Http\Middleware\StartApplicationSession;
use Application\Models\Identity\Identity;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Exceptions\Handler as LaravelHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInputPort;
use Source\Identity\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInterface;
use Source\Identity\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceOutputPort;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class WithdrawalCsrfTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->middlewareGroup('auth.api', [EnsureAuthenticated::class, EnsureAccountActive::class]);
        $router->aliasMiddleware('resolve.actor', ResolveActorContext::class);
        $router->middlewareGroup('session', [EncryptCookies::class, StartApplicationSession::class, PreventRequestForgery::class]);
        $router->middleware('session')->prefix('api/v1/identity')->group(dirname(__DIR__, 4) . '/routes/v1/identity_api.php');
        $router->middleware('session')->post('/api/csrf-probe', static fn () => response()->noContent());
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Exercise Laravel's production CSRF branch, which is bypassed in the testing environment.
        $this->app()->instance('env', 'local');
        $handler = $this->app()->make(ExceptionHandler::class);
        $this->assertInstanceOf(LaravelHandler::class, $handler);
        $handler->renderable($this->app()->make(Handler::class));
        Request::enableHttpMethodParameterOverride();
    }

    /** @return array<string, array{string, ?string, bool}> */
    public static function requests(): array
    {
        return [
            'missing token' => ['DELETE', null, false],
            'invalid token' => ['DELETE', 'invalid', false],
            'valid token' => ['DELETE', 'session-token', true],
            'forged method override' => ['POST', null, false],
            'invalid method override token' => ['POST', 'invalid', false],
            'valid method override token' => ['POST', 'session-token', true],
        ];
    }

    public function testAnonymousBrowserCanAcquireCsrfAndSessionCookies(): void
    {
        $response = $this->get('/api/v1/identity/auth/csrf-token');

        $response->assertNoContent();
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie;
        }
        $this->assertArrayHasKey('XSRF-TOKEN', $cookies);
        $this->assertArrayHasKey(config()->string('session.cookie'), $cookies);
        $this->assertNotEmpty($cookies['XSRF-TOKEN']->getValue());
        $this->assertFalse($cookies['XSRF-TOKEN']->isHttpOnly());
        $sessionCookie = $cookies[config()->string('session.cookie')];
        $this->assertTrue($sessionCookie->isHttpOnly());
        $encryptedSessionId = $sessionCookie->getValue();
        $this->assertIsString($encryptedSessionId);
        $this->assertNotSame(session()->getId(), $encryptedSessionId);
        $this->assertSame(session()->getId(), CookieValuePrefix::remove(Crypt::decryptString($encryptedSessionId)));
    }

    #[DataProvider('requests')]
    public function testCsrfProtectsTheActualAuthenticatedWithdrawalRoute(string $method, ?string $token, bool $allowed): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        DB::table('account_principals')->insert(['id' => StrTestHelper::generateUuid(), 'identity_id' => (string) $identityIdentifier, 'account_id' => $accountId]);
        $this->actingAs(Identity::query()->findOrFail((string) $identityIdentifier));
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('isCurrentSessionValid')->andReturn(true);
        $auth->shouldNotReceive('invalidateAllSessions');
        $auth->shouldNotReceive('logout');
        $this->app()->instance(AuthServiceInterface::class, $auth);
        $withdraw = Mockery::mock(WithdrawFromServiceInterface::class);
        if ($allowed) {
            $withdraw->shouldReceive('process')->once()->with(Mockery::on(static fn (WithdrawFromServiceInputPort $input): bool => (string) $input->identityIdentifier() === (string) $identityIdentifier), Mockery::type(WithdrawFromServiceOutputPort::class));
        } else {
            $withdraw->shouldNotReceive('process');
        }
        $this->app()->instance(WithdrawFromServiceInterface::class, $withdraw);
        $csrf = $this->get('/api/v1/identity/auth/csrf-token');
        $csrf->assertNoContent();
        $cookies = [];
        foreach ($csrf->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }
        $this->assertArrayHasKey('XSRF-TOKEN', $cookies);
        $this->assertArrayHasKey(config()->string('session.cookie'), $cookies);
        $this->assertNotEmpty($cookies['XSRF-TOKEN']);
        $parameters = ['confirmationIdentityName' => 'test-identity'] + ($method === 'POST' ? ['_method' => 'DELETE'] : []);
        $headers = ['HTTP_ACCEPT' => 'application/json', 'HTTP_SEC_FETCH_SITE' => 'cross-site'];
        if ($token !== null) {
            $headers['HTTP_X_XSRF_TOKEN'] = $allowed ? $cookies['XSRF-TOKEN'] : $token;
        }
        $response = $this->call($method, '/api/v1/identity/identities/me', $parameters, $cookies, [], $headers);
        $response->assertStatus($allowed ? 204 : 419);
        if (! $allowed) {
            $response->assertJsonPath('code', 'csrf_token_mismatch');
        }
    }

    public function testCommonMiddlewareRejectsForgedMutationAndAcceptsMatchingToken(): void
    {
        $csrf = $this->get('/api/v1/identity/auth/csrf-token');
        $cookies = [];
        foreach ($csrf->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }
        $headers = ['HTTP_ACCEPT' => 'application/json', 'HTTP_SEC_FETCH_SITE' => 'cross-site'];

        $this->call('POST', '/api/csrf-probe', [], $cookies, [], $headers)
            ->assertStatus(419)->assertJsonPath('code', 'csrf_token_mismatch');
        $headers['HTTP_X_XSRF_TOKEN'] = $cookies['XSRF-TOKEN'];
        $this->call('POST', '/api/csrf-probe', [], $cookies, [], $headers)->assertNoContent();
    }

    public function testMiddlewareReturnsLocalizedJsonWithoutExceptionHandlerOrJsonAcceptHeader(): void
    {
        $this->withoutExceptionHandling();

        $this->call('POST', '/api/csrf-probe', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'ja',
            'HTTP_SEC_FETCH_SITE' => 'cross-site',
        ])->assertStatus(419)->assertExactJson([
            'status' => 419,
            'title' => 'Page Expired',
            'detail' => error_message('csrf_token_mismatch', 'ja'),
            'code' => 'csrf_token_mismatch',
        ]);
    }

    public function testProtectionIsSharedWithOtherIdentityMutations(): void
    {
        $routes = $this->app()->make('router')->getRoutes();
        $withdraw = $routes->match(Request::create('/api/v1/identity/identities/me', 'DELETE'));
        $update = $routes->match(Request::create('/api/v1/identity/identities/me', 'PATCH'));
        $this->assertContains(PreventRequestForgery::class, $this->app()->make('router')->gatherRouteMiddleware($withdraw));
        $this->assertContains(PreventRequestForgery::class, $this->app()->make('router')->gatherRouteMiddleware($update));
    }
}
