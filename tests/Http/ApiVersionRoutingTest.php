<?php

declare(strict_types=1);

namespace Tests\Http;

use Application\Http\Middleware\PreventRequestForgery;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
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

    public function testAllBusinessRoutesUseFixedV1PrefixAndSessionProtection(): void
    {
        $contexts = [];
        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/')) {
                continue;
            }

            $this->assertMatchesRegularExpression('#^api/v1/(identity|account|wiki|monetization|site-management)/#', $route->uri());
            $contexts[] = explode('/', $route->uri())[2];
            $this->assertContains('api', $route->gatherMiddleware());
            $this->assertContains('session', $route->gatherMiddleware());
            $this->assertContains(PreventRequestForgery::class, $this->app['router']->gatherRouteMiddleware($route), $route->uri());
        }
        $contexts = array_values(array_unique($contexts));
        sort($contexts);
        // Monetization currently has no enabled endpoints; moving its file must not enable them.
        $this->assertSame(['account', 'identity', 'site-management', 'wiki'], $contexts);
        foreach (['identity', 'account', 'wiki', 'monetization', 'site_management'] as $context) {
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
