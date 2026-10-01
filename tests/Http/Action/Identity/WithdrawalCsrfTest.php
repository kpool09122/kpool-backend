<?php

declare(strict_types=1);

namespace Tests\Http\Action\Identity;

use Application\Http\Exceptions\Handler;
use Application\Http\Middleware\EnsureAccountActive;
use Application\Http\Middleware\EnsureAuthenticated;
use Application\Http\Middleware\ResolveActorContext;
use Application\Http\Middleware\StartApplicationSession;
use Application\Models\Identity\Identity;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler as LaravelHandler;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Application\UseCase\Command\WithdrawIdentity\WithdrawIdentityInterface;
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
        $router->middleware(StartApplicationSession::class)->prefix('api/identity')->group(dirname(__DIR__, 4) . '/routes/identity_api.php');
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Exercise Laravel's production CSRF branch, which is bypassed in the testing environment.
        $this->app()->instance('env', 'local');
        $handler = $this->app()->make(ExceptionHandler::class);
        $this->assertInstanceOf(LaravelHandler::class, $handler);
        $handler->renderable(new Handler());
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
        $response = $this->get('/api/identity/auth/csrf-token');

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
        $withdraw = Mockery::mock(WithdrawIdentityInterface::class);
        if ($allowed) {
            $withdraw->shouldReceive('process')->once()->with(Mockery::on(static fn (IdentityIdentifier $actual): bool => (string) $actual === (string) $identityIdentifier));
        } else {
            $withdraw->shouldNotReceive('process');
        }
        $this->app()->instance(WithdrawIdentityInterface::class, $withdraw);
        $csrf = $this->get('/api/identity/auth/csrf-token');
        $csrf->assertNoContent();
        $cookies = [];
        foreach ($csrf->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }
        $this->assertArrayHasKey('XSRF-TOKEN', $cookies);
        $this->assertArrayHasKey(config()->string('session.cookie'), $cookies);
        $this->assertNotEmpty($cookies['XSRF-TOKEN']);
        $parameters = $method === 'POST' ? ['_method' => 'DELETE'] : [];
        $headers = ['HTTP_ACCEPT' => 'application/json', 'HTTP_SEC_FETCH_SITE' => 'cross-site'];
        if ($token !== null) {
            $headers['HTTP_X_XSRF_TOKEN'] = $allowed ? $cookies['XSRF-TOKEN'] : $token;
        }
        $response = $this->call($method, '/api/identity/identities/me', $parameters, $cookies, [], $headers);
        $response->assertStatus($allowed ? 204 : 419);
        if (! $allowed) {
            $response->assertJsonPath('code', 'csrf_token_mismatch');
        }
    }

    public function testProtectionIsAttachedOnlyToTheWithdrawalRoute(): void
    {
        $routes = $this->app()->make('router')->getRoutes();
        $withdraw = $routes->match(Request::create('/api/identity/identities/me', 'DELETE'));
        $update = $routes->match(Request::create('/api/identity/identities/me', 'PATCH'));
        $this->assertContains(PreventRequestForgery::class, $withdraw->gatherMiddleware());
        $this->assertNotContains(PreventRequestForgery::class, $update->gatherMiddleware());
    }
}
