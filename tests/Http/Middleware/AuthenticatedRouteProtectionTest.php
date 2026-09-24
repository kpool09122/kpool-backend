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
        $router->aliasMiddleware('resolve.account', \Application\Http\Middleware\ResolveAccountContext::class);
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
        RouteFacade::middleware(['api', 'session'])
            ->prefix('api/site-management')
            ->group($routePath('siteManagiment_public_api.php'));
        RouteFacade::prefix('webhook')
            ->group($routePath('webhook.php'));
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

    #[DataProvider('disabledRouteProvider')]
    public function testUnusedRoutesAreNotRegistered(string $method, string $uri): void
    {
        foreach (RouteFacade::getRoutes()->getRoutes() as $route) {
            $this->assertFalse(
                $route->uri() === $uri && in_array($method, $route->methods(), true),
                sprintf('%s %s must not be registered.', $method, $uri),
            );
        }
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

    public function testIdentityRoutesWithoutAuthApiMiddlewareMatchPublicRouteWhitelist(): void
    {
        $actualPublicIdentityRouteUris = [];

        foreach (RouteFacade::getRoutes()->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/identity/')) {
                continue;
            }

            if (! in_array('auth.api', $route->gatherMiddleware(), true)) {
                $actualPublicIdentityRouteUris[] = $route->uri();
            }
        }

        $actualPublicIdentityRouteUris = array_values(array_unique($actualPublicIdentityRouteUris));
        sort($actualPublicIdentityRouteUris);

        $expected = [
            'api/identity/auth/login',
            'api/identity/auth/passkeys/authentication',
            'api/identity/auth/passkeys/authentication/options',
            'api/identity/auth/passkeys/registration/options',
            'api/identity/auth/register',
            'api/identity/auth/social/{provider}/callback',
            'api/identity/auth/social/{provider}/redirect',
            'api/identity/auth/verify-email',
        ];
        sort($expected);

        $this->assertSame($expected, $actualPublicIdentityRouteUris);
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
            'identity: list passkeys' => ['GET', '/api/identity/auth/passkeys'],
            'identity: logout' => ['POST', '/api/identity/auth/logout'],
            'identity: add passkey options' => ['POST', '/api/identity/auth/passkeys/addition/options'],
            'identity: add passkey' => ['POST', '/api/identity/auth/passkeys/addition'],
            'account: switch account' => ['POST', '/api/account/accounts/switch'],
            'identity: update me' => ['PATCH', '/api/identity/identities/me'],

            // Account: signup 用の POST /accounts 以外は認証必須
            'account: get account' => ['GET', '/api/account/accounts/00000000-0000-0000-0000-000000000001'],
            'account: update account' => ['PATCH', '/api/account/accounts/00000000-0000-0000-0000-000000000001'],
            'account: list my account documents' => ['GET', '/api/account/my/documents'],
            'account: view account document' => ['GET', '/api/account/accounts/00000000-0000-0000-0000-000000000001/documents/business_registration'],
            'account: list account category change requests' => ['GET', '/api/account/account-category-change-requests'],
            'account: request delegation' => ['POST', '/api/account/delegations'],
            'account: create invitation' => ['POST', '/api/account/invitations'],
            'account: list members' => ['GET', '/api/account/members'],
            'account: list principal groups' => ['GET', '/api/account/principal-groups'],
            'account: update principal group members' => ['PATCH', '/api/account/principal-groups/members'],

            // Wiki command / review / draft / admin / auxiliary edit APIs
            'wiki: create wiki' => ['POST', '/api/wiki/wiki/create'],
            'wiki: master search' => ['GET', '/api/wiki/wikis/ja/masters'],
            'wiki: version inconsistencies' => ['GET', '/api/wiki/wikis/version-inconsistencies'],
            'wiki: agency draft' => ['GET', '/api/wiki/wiki/agency/00000000-0000-0000-0000-000000000003/draft'],
            'wiki: group draft' => ['GET', '/api/wiki/wiki/group/00000000-0000-0000-0000-000000000004/draft'],
            'wiki: song draft' => ['GET', '/api/wiki/wiki/song/00000000-0000-0000-0000-000000000005/draft'],
            'wiki: talent draft' => ['GET', '/api/wiki/wiki/talent/00000000-0000-0000-0000-000000000006/draft'],
            'wiki: my draft' => ['GET', '/api/wiki/wiki/ja/group/group-slug/my/draft'],
            'wiki: my owned wikis' => ['GET', '/api/wiki/my/owned-wikis'],
            'wiki: draft wikis' => ['GET', '/api/wiki/draft-wikis'],
            'wiki: related wikis' => ['GET', '/api/wiki/wiki/agency/00000000-0000-0000-0000-000000000014/related-wikis'],
            'wiki: draft images' => ['GET', '/api/wiki/draft-images'],
            'wiki: image deletion requests' => ['GET', '/api/wiki/image-deletion-requests'],
            'wiki: uploaded images' => ['GET', '/api/wiki/images'],
            'wiki: image upload' => ['POST', '/api/wiki/image/upload'],
            'wiki: current principal' => ['GET', '/api/wiki/principal/me'],
            'wiki: create principal' => ['POST', '/api/wiki/principal/create'],
            'wiki: official certifications list' => ['GET', '/api/wiki/official-certifications'],
            'wiki: my official certifications list' => ['GET', '/api/wiki/my/official-certifications'],
            'wiki: official certification request' => ['POST', '/api/wiki/official-certification/request'],
            'wiki: official certification owned wikis sync' => ['PUT', '/api/wiki/official-certification/owned-wikis'],
            'wiki: official certification approve' => ['POST', '/api/wiki/official-certification/00000000-0000-0000-0000-000000000012/approve'],
            'wiki: official certification reject' => ['POST', '/api/wiki/official-certification/00000000-0000-0000-0000-000000000013/reject'],
        ];
    }

    /**
     * @return array<string, array{string, string, array<int, string>}>
     */
    public static function contextAwareAuthenticatedRouteProvider(): array
    {
        return [
            'identity authenticated routes resolve actor for me' => ['GET', '/api/identity/auth/me', ['resolve.actor']],
            'identity add passkey options resolves actor' => ['POST', '/api/identity/auth/passkeys/addition/options', ['resolve.actor']],
            'identity add passkey resolves actor' => ['POST', '/api/identity/auth/passkeys/addition', ['resolve.actor']],
            'identity authenticated routes resolve actor for passkeys' => ['GET', '/api/identity/auth/passkeys', ['resolve.actor']],
            'account authenticated routes resolve actor and account' => ['POST', '/api/account/delegations', ['resolve.actor', 'resolve.account']],
            'account members resolve actor and account' => ['GET', '/api/account/members', ['resolve.actor', 'resolve.account']],
            'account principal groups resolve actor and account' => ['GET', '/api/account/principal-groups', ['resolve.actor', 'resolve.account']],
            'account update principal group members resolves actor and account' => ['PATCH', '/api/account/principal-groups/members', ['resolve.actor', 'resolve.account']],
            'account get resolves actor and account' => ['GET', '/api/account/accounts/00000000-0000-0000-0000-000000000001', ['resolve.actor', 'resolve.account']],
            'account list my documents resolves actor and account' => ['GET', '/api/account/my/documents', ['resolve.actor', 'resolve.account']],
            'account view document resolves actor and account' => ['GET', '/api/account/accounts/00000000-0000-0000-0000-000000000001/documents/business_registration', ['resolve.actor', 'resolve.account']],
            'account list account category change requests resolves actor and account' => ['GET', '/api/account/account-category-change-requests', ['resolve.actor', 'resolve.account']],
            'account update resolves actor and account' => ['PATCH', '/api/account/accounts/00000000-0000-0000-0000-000000000001', ['resolve.actor', 'resolve.account']],
            'wiki commands resolve actor and wiki' => ['POST', '/api/wiki/wiki/create', ['resolve.actor', 'resolve.wiki']],
            'wiki my draft resolves actor and wiki' => ['GET', '/api/wiki/wiki/ja/group/group-slug/my/draft', ['resolve.actor', 'resolve.wiki']],
            'wiki my owned wikis resolves actor and account' => ['GET', '/api/wiki/my/owned-wikis', ['resolve.actor', 'resolve.account']],
            'wiki related wikis resolves actor, account and wiki' => ['GET', '/api/wiki/wiki/agency/00000000-0000-0000-0000-000000000014/related-wikis', ['resolve.actor', 'resolve.account', 'resolve.wiki']],
            'wiki current principal resolves actor and account' => ['GET', '/api/wiki/principal/me', ['resolve.actor', 'resolve.account']],
            'wiki update principal group members resolves actor, account and wiki' => ['PATCH', '/api/wiki/principal-groups/members', ['resolve.actor', 'resolve.account', 'resolve.wiki']],
            'wiki image upload resolves actor and wiki' => ['POST', '/api/wiki/image/upload', ['resolve.actor', 'resolve.wiki']],
            'wiki image deletion requests resolves actor and wiki' => ['GET', '/api/wiki/image-deletion-requests', ['resolve.actor', 'resolve.wiki']],
            'wiki official certifications resolves actor and wiki' => ['GET', '/api/wiki/official-certifications', ['resolve.actor', 'resolve.wiki']],
            'wiki my official certifications resolves actor, account and wiki' => ['GET', '/api/wiki/my/official-certifications', ['resolve.actor', 'resolve.account', 'resolve.wiki']],
            'wiki official certification owned sync resolves actor, account and wiki' => ['PUT', '/api/wiki/official-certification/owned-wikis', ['resolve.actor', 'resolve.account', 'resolve.wiki']],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function publicRouteProvider(): array
    {
        return [
            // Identity: 認証開始に必要な公開API
            'identity: verify email' => ['POST', '/api/identity/auth/verify-email'],
            'identity: register' => ['POST', '/api/identity/auth/register'],
            'identity: passkey authentication options' => ['POST', '/api/identity/auth/passkeys/authentication/options'],
            'identity: passkey registration options' => ['POST', '/api/identity/auth/passkeys/registration/options'],
            'identity: login' => ['POST', '/api/identity/auth/login'],
            'identity: social redirect' => ['GET', '/api/identity/auth/social/google/redirect'],
            'identity: social callback' => ['GET', '/api/identity/auth/social/google/callback'],

            // Account: signup フローで利用する公開例外
            'account: create account' => ['POST', '/api/account/accounts'],

            // SiteManagement: kpool-frontend の問い合わせフォームで利用する公開API
            'site management: submit contact' => ['POST', '/api/site-management/contact/submit/v1'],

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
     * @return array<string, array{string, string}>
     */
    public static function disabledRouteProvider(): array
    {
        return [
            'identity: send auth code' => ['POST', 'api/identity/auth/send-auth-code'],
            'identity: get identity profile' => ['GET', 'api/identity/auth/identities/{identityIdentifier}/profile'],
            'account: delete account' => ['DELETE', 'api/account/accounts/{accountId}'],
            'account: create delegation permission' => ['POST', 'api/account/delegation-permissions'],
            'account: delete delegation permission' => ['DELETE', 'api/account/delegation-permissions/{delegationPermissionId}'],
            'account: create principal group' => ['POST', 'api/account/principal-groups'],
            'account: add principal group member' => ['POST', 'api/account/principal-groups/{principalGroupId}/add-member'],
            'account: remove principal group member' => ['POST', 'api/account/principal-groups/{principalGroupId}/remove-member'],
            'account: delete principal group' => ['DELETE', 'api/account/principal-groups/{principalGroupId}'],
            'account: terminate affiliation' => ['POST', 'api/account/affiliations/{affiliationId}/terminate'],
            'wiki: merge' => ['POST', 'api/wiki/wiki/{wikiId}/merge'],
            'wiki: rollback' => ['POST', 'api/wiki/wiki/{wikiId}/rollback'],
            'wiki: delete image' => ['DELETE', 'api/wiki/image/{imageId}'],
            'wiki: unhide image' => ['POST', 'api/wiki/image/{imageId}/unhide'],
            'wiki: create principal group' => ['POST', 'api/wiki/principal-group/create'],
            'wiki: add principal group member' => ['POST', 'api/wiki/principal-group/{principalGroupId}/add-member'],
            'wiki: remove principal group member' => ['POST', 'api/wiki/principal-group/{principalGroupId}/remove-member'],
            'wiki: delete principal group' => ['DELETE', 'api/wiki/principal-group/{principalGroupId}'],
            'wiki: attach role' => ['POST', 'api/wiki/principal-group/{principalGroupId}/attach-role'],
            'wiki: detach role' => ['POST', 'api/wiki/principal-group/{principalGroupId}/detach-role'],
            'wiki: create role' => ['POST', 'api/wiki/role/create'],
            'wiki: delete role' => ['DELETE', 'api/wiki/role/{roleId}'],
            'wiki: attach policy' => ['POST', 'api/wiki/role/{roleId}/attach-policy'],
            'wiki: detach policy' => ['POST', 'api/wiki/role/{roleId}/detach-policy'],
            'wiki: create policy' => ['POST', 'api/wiki/policy/create'],
            'wiki: delete policy' => ['DELETE', 'api/wiki/policy/{policyId}'],
            'wiki: save video link' => ['POST', 'api/wiki/video-link/save'],
            'monetization: provision account' => ['POST', 'api/monetization/accounts'],
            'monetization: onboard seller' => ['POST', 'api/monetization/accounts/{monetizationAccountId}/onboard-seller'],
            'monetization: register payment method' => ['POST', 'api/monetization/accounts/{monetizationAccountId}/register-payment-method'],
            'monetization: sync payout account' => ['POST', 'api/monetization/accounts/sync-payout-account'],
            'monetization: authorize payment' => ['POST', 'api/monetization/payments/authorize'],
            'monetization: capture payment' => ['POST', 'api/monetization/payments/{paymentId}/capture'],
            'monetization: refund payment' => ['POST', 'api/monetization/payments/{paymentId}/refund'],
            'monetization: create invoice' => ['POST', 'api/monetization/invoices'],
            'monetization: record payment' => ['POST', 'api/monetization/invoices/{invoiceId}/record-payment'],
            'monetization: execute transfer' => ['POST', 'api/monetization/transfers/{transferId}/execute'],
            'monetization: settle revenue' => ['POST', 'api/monetization/settlements/settle-revenue'],
            'webhook: stripe' => ['POST', 'webhook/stripe'],
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
