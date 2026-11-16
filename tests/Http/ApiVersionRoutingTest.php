<?php

declare(strict_types=1);

namespace Tests\Http;

use Application\Http\Middleware\AuthenticateApiRequest;
use Application\Http\Middleware\AuthorizeApiAccount;
use Application\Http\Middleware\EnsureAccountActive;
use Application\Http\Middleware\EnsurePrincipalActive;
use Application\Http\Middleware\PreventRequestForgery;
use Application\Http\Middleware\ResolveAccountContext;
use Application\Http\Middleware\StartApplicationSession;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiVersionRoutingTest extends TestCase
{
    public function createApplication(): Application
    {
        /** @var Application $app */
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        $app->make(HttpKernel::class);

        return $app;
    }

    public function testAuthenticationModeIsSelectedBeforeCookieDecryptionAndSessionStartOnEveryApiRoute(): void
    {
        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }
            $middleware = $this->app['router']->gatherRouteMiddleware($route);
            $authentication = array_search(AuthenticateApiRequest::class, $middleware, true);
            self::assertIsInt($authentication, $route->uri());
            foreach ([EncryptCookies::class, StartApplicationSession::class, PreventRequestForgery::class] as $class) {
                $position = array_search($class, $middleware, true);
                self::assertIsInt($position, $route->uri());
                self::assertLessThan($position, $authentication, $route->uri());
            }
        }
    }

    public function testEveryApiRouteEnforcesConnectionAccountBeforeRouteSpecificAccountResolution(): void
    {
        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }
            $middleware = $this->app['router']->gatherRouteMiddleware($route);
            $authorization = array_search(AuthorizeApiAccount::class, $middleware, true);
            self::assertIsInt($authorization, $route->uri());
            $authentication = array_search(AuthenticateApiRequest::class, $middleware, true);
            self::assertIsInt($authentication, $route->uri());
            self::assertLessThan($authorization, $authentication, $route->uri());
            foreach ([EnsureAccountActive::class, EnsurePrincipalActive::class, ResolveAccountContext::class] as $class) {
                $position = array_search($class, $middleware, true);
                if (is_int($position)) {
                    self::assertLessThan($position, $authorization, $route->uri());
                }
            }
        }
    }

    public function testAllBusinessRoutesUseFixedV1PrefixAndSessionProtection(): void
    {
        $contexts = [];
        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/')) {
                continue;
            }

            $this->assertMatchesRegularExpression('#^api/v1/(identity|account|wiki|monetization|site-management|analyze)/#', $route->uri());
            $contexts[] = explode('/', $route->uri())[2];
            $this->assertContains('api', $route->gatherMiddleware());
            $this->assertContains('session', $route->gatherMiddleware());
            $this->assertContains(PreventRequestForgery::class, $this->app['router']->gatherRouteMiddleware($route), $route->uri());
        }
        $contexts = array_values(array_unique($contexts));
        sort($contexts);
        // Monetization currently has no enabled endpoints; moving its file must not enable them.
        $this->assertSame(['account', 'analyze', 'identity', 'site-management', 'wiki'], $contexts);
        foreach (['identity', 'account', 'wiki', 'monetization', 'site_management', 'analyze'] as $context) {
            $this->assertFileExists(__DIR__ . '/../../routes/v1/' . $context . '_api.php');
            $this->assertFileDoesNotExist(__DIR__ . '/../../routes/' . $context . '_api.php');
        }
    }

    public function testOldAndUnimplementedVersionsDoNotResolveForAnyEnabledEndpoint(): void
    {
        $routes = $this->app['router']->getRoutes();
        $count = 0;
        foreach ($routes->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }
            foreach (['api/', 'api/v2/'] as $prefix) {
                $uri = str_replace('api/v1/', $prefix, $route->uri());
                foreach ($route->methods() as $method) {
                    $this->assertIsString($method);

                    try {
                        $routes->match(Request::create('/' . $uri, $method));
                        $this->fail($method . ' ' . $uri . ' must not resolve.');
                    } catch (NotFoundHttpException) {
                        $count++;
                    }
                }
            }
        }
        $this->assertGreaterThan(0, $count);
    }

    public function testContactSubmissionHasNoPathVersionAndHealthIsUnchanged(): void
    {
        $routes = $this->app['router']->getRoutes();
        $route = $routes->match(Request::create('/api/v1/site-management/contact/submit', 'POST'));
        $this->assertSame('api/v1/site-management/contact/submit', $route->uri());
        $this->assertSame([], $route->parameterNames());
        $this->assertSame(['POST'], $route->methods());
        $this->assertContains('rate-limit:screen,command', $route->gatherMiddleware());
        $this->assertSame('health', $routes->match(Request::create('/health'))->uri());
        $this->expectException(NotFoundHttpException::class);
        $routes->match(Request::create('/api/v1/site-management/contact/submit/v1', 'POST'));
    }
}
