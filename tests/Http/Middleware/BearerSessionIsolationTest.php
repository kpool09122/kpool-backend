<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Middleware\PreventRequestForgery;
use Application\Http\Middleware\StartApplicationSession;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Psr\Log\NullLogger;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Tests\Helper\CreateApiAuthenticationContext;
use Tests\TestCase;

class BearerSessionIsolationTest extends TestCase
{
    public function testVerifiedBearerDoesNotStartASessionOrRequireCsrf(): void
    {
        $request = Request::create('/api/v1/wiki/wiki/create', 'POST');
        $request->attributes->set(ApiAuthenticationContext::class, CreateApiAuthenticationContext::create());
        $session = new StartApplicationSession(app(SessionManager::class), new NullLogger());
        $csrf = new PreventRequestForgery(app(), app(Encrypter::class));
        app()->instance('env', 'local');
        $response = $session->handle($request, fn (Request $request) => $csrf->handle($request, static function (Request $request) {
            self::assertFalse($request->hasSession());

            return response('ok');
        }));
        self::assertSame('ok', $response->getContent());
        self::assertSame([], $response->headers->getCookies());
    }

    public function testRawBearerHeaderCannotSkipCsrf(): void
    {
        app()->instance('env', 'local');
        $request = Request::create('/api/v1/wiki/wiki/create', 'POST', server: ['HTTP_AUTHORIZATION' => 'Bearer forged']);
        $request->setLaravelSession(app(Session::class));
        $csrf = new PreventRequestForgery(app(), app(Encrypter::class));
        $response = $csrf->handle($request, static fn () => response('must not run'));
        self::assertSame(419, $response->getStatusCode());
    }
}
