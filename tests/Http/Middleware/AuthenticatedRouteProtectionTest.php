<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Exceptions\UnauthorizedHttpException;
use Application\Http\Middleware\EnsureAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route as RouteFacade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticatedRouteProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $router = $this->app['router'];
        $router->aliasMiddleware('auth.api', \Application\Http\Middleware\EnsureAuthenticated::class);
        $router->aliasMiddleware('resolve.actor', \Application\Http\Middleware\ResolveActorContext::class);
        $router->aliasMiddleware('resolve.wiki', \Application\Http\Middleware\ResolveWikiContext::class);
        $router->aliasMiddleware('session', \Illuminate\Session\Middleware\StartSession::class);

        $routePath = static fn (string $file): string => __DIR__ . '/../../../routes/' . $file;

        RouteFacade::middleware(['api', 'session'])
            ->prefix('api/identity')
            ->group($routePath('identity_api.php'));
        RouteFacade::middleware(['api', 'session', 'auth.api', 'resolve.actor'])
            ->prefix('api/monetization')
            ->group($routePath('monetization_api.php'));
        RouteFacade::middleware(['api', 'session'])
            ->prefix('api/account')
            ->group($routePath('account_api.php'));
        RouteFacade::middleware(['api', 'session'])
            ->prefix('api/wiki')
            ->group($routePath('wiki_private_api.php'));
    }

    #[DataProvider('authenticatedRouteProvider')]
    public function testAuthenticatedRoutesIncludeAuthApiMiddleware(string $method, string $uri): void
    {
        $this->assertContains('auth.api', $this->routeFor($method, $uri)->gatherMiddleware());
    }

    /**
     * @param array<int, string> $expectedContextMiddleware
     */
    #[DataProvider('contextAwareAuthenticatedRouteProvider')]
    public function testContextAwareAuthenticatedRoutesIncludeRequiredContextMiddleware(
        string $method,
        string $uri,
        array $expectedContextMiddleware,
    ): void {
        $middleware = $this->routeFor($method, $uri)->gatherMiddleware();

        foreach ($expectedContextMiddleware as $expectedMiddleware) {
            $this->assertContains($expectedMiddleware, $middleware);
        }
    }

    #[DataProvider('publicRouteProvider')]
    public function testPublicExceptionRoutesDoNotIncludeAuthApiMiddleware(string $method, string $uri): void
    {
        $this->assertNotContains('auth.api', $this->routeFor($method, $uri)->gatherMiddleware());
    }

    public function testEnsureAuthenticatedMiddlewareRejectsUnauthenticatedRequests(): void
    {
        Auth::shouldReceive('check')->andReturn(false);

        $request = Request::create('/api/wiki/principal/me', 'GET');
        $request->headers->set('Accept-Language', 'en');
        $middleware = new EnsureAuthenticated();

        $this->expectException(UnauthorizedHttpException::class);

        $middleware->handle($request, fn () => response('ok'));
    }

    public function testWikiRoutesWithoutAuthApiMiddlewareMatchPublicRouteWhitelist(): void
    {
        $actualPublicWikiRouteUris = [];

        foreach (RouteFacade::getRoutes()->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/wiki/')) {
                continue;
            }

            if (! in_array('auth.api', $route->gatherMiddleware(), true)) {
                $actualPublicWikiRouteUris[] = $route->uri();
            }
        }

        $actualPublicWikiRouteUris = array_values(array_unique($actualPublicWikiRouteUris));
        sort($actualPublicWikiRouteUris);

        $expectedPublicWikiRouteUris = self::publicWikiRouteUris();
        sort($expectedPublicWikiRouteUris);

        $this->assertSame($expectedPublicWikiRouteUris, $actualPublicWikiRouteUris);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function authenticatedRouteProvider(): array
    {
        return [
            // Identity: 認証開始系以外の操作は認証必須
            'identity: me' => ['GET', '/api/identity/auth/me'],
            'identity: logout' => ['POST', '/api/identity/auth/logout'],
            'identity: switch-identity' => ['POST', '/api/identity/auth/switch-identity'],
            'identity: update me' => ['PATCH', '/api/identity/identities/me'],

            // Account: signup 用の POST /accounts 以外は認証必須
            'account: delete account' => ['DELETE', '/api/account/accounts/00000000-0000-0000-0000-000000000001'],
            'account: request delegation' => ['POST', '/api/account/delegations'],
            'account: create invitation' => ['POST', '/api/account/invitations'],

            // Monetization: bootstrap/app.php のグループ設定で全 route が認証必須
            'monetization: provision account' => ['POST', '/api/monetization/accounts'],
            'monetization: authorize payment' => ['POST', '/api/monetization/payments/authorize'],
            'monetization: execute transfer' => ['POST', '/api/monetization/transfers/00000000-0000-0000-0000-000000000002/execute'],

            // Wiki command / review / draft / admin / auxiliary edit APIs
            'wiki: create wiki' => ['POST', '/api/wiki/wiki/create'],
            'wiki: master search' => ['GET', '/api/wiki/wikis/ja/masters'],
            'wiki: version inconsistencies' => ['GET', '/api/wiki/wikis/version-inconsistencies'],
            'wiki: agency draft' => ['GET', '/api/wiki/wiki/agency/00000000-0000-0000-0000-000000000003/draft'],
            'wiki: group draft' => ['GET', '/api/wiki/wiki/group/00000000-0000-0000-0000-000000000004/draft'],
            'wiki: song draft' => ['GET', '/api/wiki/wiki/song/00000000-0000-0000-0000-000000000005/draft'],
            'wiki: talent draft' => ['GET', '/api/wiki/wiki/talent/00000000-0000-0000-0000-000000000006/draft'],
            'wiki: my draft' => ['GET', '/api/wiki/wiki/ja/group/group-slug/my/draft'],
            'wiki: draft wikis' => ['GET', '/api/wiki/draft-wikis'],
            'wiki: draft images' => ['GET', '/api/wiki/draft-images'],
            'wiki: uploaded images' => ['GET', '/api/wiki/images'],
            'wiki: image upload' => ['POST', '/api/wiki/image/upload'],
            'wiki: current principal' => ['GET', '/api/wiki/principal/me'],
            'wiki: create principal' => ['POST', '/api/wiki/principal/create'],
            'wiki: create principal group' => ['POST', '/api/wiki/principal-group/create'],
            'wiki: add principal group member' => ['POST', '/api/wiki/principal-group/00000000-0000-0000-0000-000000000007/add-member'],
            'wiki: delete principal group' => ['DELETE', '/api/wiki/principal-group/00000000-0000-0000-0000-000000000008'],
            'wiki: create role' => ['POST', '/api/wiki/role/create'],
            'wiki: delete role' => ['DELETE', '/api/wiki/role/00000000-0000-0000-0000-000000000009'],
            'wiki: attach policy' => ['POST', '/api/wiki/role/00000000-0000-0000-0000-000000000010/attach-policy'],
            'wiki: create policy' => ['POST', '/api/wiki/policy/create'],
            'wiki: delete policy' => ['DELETE', '/api/wiki/policy/00000000-0000-0000-0000-000000000011'],
            'wiki: official certification request' => ['POST', '/api/wiki/official-certification/request'],
            'wiki: official certification approve' => ['POST', '/api/wiki/official-certification/00000000-0000-0000-0000-000000000012/approve'],
            'wiki: official certification reject' => ['POST', '/api/wiki/official-certification/00000000-0000-0000-0000-000000000013/reject'],
            'wiki: video link save' => ['POST', '/api/wiki/video-link/save'],
        ];
    }

    /**
     * @return array<string, array{string, string, array<int, string>}>
     */
    public static function contextAwareAuthenticatedRouteProvider(): array
    {
        return [
            'identity authenticated routes resolve actor' => ['GET', '/api/identity/auth/me', ['resolve.actor']],
            'account authenticated routes resolve actor' => ['POST', '/api/account/delegations', ['resolve.actor']],
            'monetization routes resolve actor from bootstrap group' => ['POST', '/api/monetization/accounts', ['resolve.actor']],
            'wiki commands resolve actor and wiki' => ['POST', '/api/wiki/wiki/create', ['resolve.actor', 'resolve.wiki']],
            'wiki my draft resolves actor and wiki' => ['GET', '/api/wiki/wiki/ja/group/group-slug/my/draft', ['resolve.actor', 'resolve.wiki']],
            'wiki current principal resolves actor' => ['GET', '/api/wiki/principal/me', ['resolve.actor']],
            'wiki image upload resolves actor and wiki' => ['POST', '/api/wiki/image/upload', ['resolve.actor', 'resolve.wiki']],
            'wiki video link resolves actor and wiki' => ['POST', '/api/wiki/video-link/save', ['resolve.actor', 'resolve.wiki']],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function publicRouteProvider(): array
    {
        return [
            // Identity: 認証開始に必要な公開API
            'identity: send auth code' => ['POST', '/api/identity/auth/send-auth-code'],
            'identity: verify email' => ['POST', '/api/identity/auth/verify-email'],
            'identity: register' => ['POST', '/api/identity/auth/register'],
            'identity: login' => ['POST', '/api/identity/auth/login'],
            'identity: social redirect' => ['GET', '/api/identity/auth/social/google/redirect'],
            'identity: social callback' => ['GET', '/api/identity/auth/social/google/callback'],

            // Account: signup フローで利用する公開例外
            'account: create account' => ['POST', '/api/account/accounts'],

            // Wiki: トップページ・Wiki一覧・Wiki詳細で必要な公開取得API
            'wiki: list wikis' => ['GET', '/api/wiki/wikis/ja'],
            'wiki: related profiles' => ['GET', '/api/wiki/wiki/ja/group-slug/related-profiles'],
            'wiki: agency detail' => ['GET', '/api/wiki/wiki/ja/agency/agency-slug'],
            'wiki: group detail' => ['GET', '/api/wiki/wiki/ja/group/group-slug'],
            'wiki: song detail' => ['GET', '/api/wiki/wiki/ja/song/song-slug'],
            'wiki: talent detail' => ['GET', '/api/wiki/wiki/ja/talent/talent-slug'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function publicWikiRouteUris(): array
    {
        return [
            'api/wiki/wiki/{language}/{slug}/related-profiles',
            'api/wiki/wiki/{language}/agency/{slug}',
            'api/wiki/wiki/{language}/group/{slug}',
            'api/wiki/wiki/{language}/song/{slug}',
            'api/wiki/wiki/{language}/talent/{slug}',
            'api/wiki/wikis/{language}',
        ];
    }

    private function routeFor(string $method, string $uri): Route
    {
        $request = Request::create($uri, $method);

        return RouteFacade::getRoutes()->match($request);
    }
}
