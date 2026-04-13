<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Exceptions\UnauthorizedHttpException;
use Application\Http\Middleware\EnsureAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class EnsureAuthenticatedTest extends TestCase
{
    public function testThrowsUnauthorizedHttpExceptionWhenNotAuthenticated(): void
    {
        Auth::shouldReceive('check')->once()->andReturn(false);

        $request = Request::create('/api/test', 'GET');
        $middleware = new EnsureAuthenticated();

        $this->expectException(UnauthorizedHttpException::class);

        $middleware->handle($request, fn () => response('ok'));
    }

    public function testPassesWhenAuthenticated(): void
    {
        Auth::shouldReceive('check')->once()->andReturn(true);

        $request = Request::create('/api/test', 'GET');
        $middleware = new EnsureAuthenticated();

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
    }
}
