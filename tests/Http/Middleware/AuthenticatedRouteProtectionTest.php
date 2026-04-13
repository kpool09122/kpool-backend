<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Exceptions\UnauthorizedHttpException;
use Application\Http\Middleware\EnsureAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticatedRouteProtectionTest extends TestCase
{
    #[DataProvider('authenticatedRouteProvider')]
    public function testUnauthenticatedRequestThrowsUnauthorizedException(string $method, string $uri): void
    {
        Auth::shouldReceive('check')->andReturn(false);

        $request = Request::create($uri, $method);
        $request->headers->set('Accept-Language', 'en');
        $middleware = new EnsureAuthenticated();

        $this->expectException(UnauthorizedHttpException::class);

        $middleware->handle($request, fn () => response('ok'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function authenticatedRouteProvider(): array
    {
        return [
            // Identity (authenticated routes)
            'identity: logout' => ['POST', '/api/identity/auth/logout'],
            'identity: switch-identity' => ['POST', '/api/identity/auth/switch-identity'],

            // Account (all routes require auth)
            'account: create account' => ['POST', '/api/account/accounts'],

            // Monetization (all routes require auth)
            'monetization: provision account' => ['POST', '/api/monetization/accounts'],

            // Wiki (all routes require auth)
            'wiki: create wiki' => ['POST', '/api/wiki/wiki/create'],
        ];
    }
}
