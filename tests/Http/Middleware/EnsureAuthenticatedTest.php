<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Exceptions\UnauthorizedHttpException;
use Application\Http\Middleware\EnsureAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Tests\TestCase;

class EnsureAuthenticatedTest extends TestCase
{
    public function testThrowsUnauthorizedHttpExceptionWhenNotAuthenticated(): void
    {
        Auth::shouldReceive('check')->once()->andReturn(false);

        $request = Request::create('/api/test', 'GET');
        /** @var AuthServiceInterface $authService */
        $authService = Mockery::mock(AuthServiceInterface::class);
        $middleware = new EnsureAuthenticated($authService);

        $this->expectException(UnauthorizedHttpException::class);

        $middleware->handle($request, fn () => response('ok'));
    }

    public function testPassesWhenAuthenticated(): void
    {
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn('01965bb2-bcc9-7c6f-8b90-89f7f217f001');
        /** @var AuthServiceInterface&\Mockery\MockInterface $authService */
        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('isCurrentSessionValid')->once()->andReturn(true);

        $request = Request::create('/api/test', 'GET');
        $middleware = new EnsureAuthenticated($authService);

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
    }
}
