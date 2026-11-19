<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\UnauthorizedHttpException;
use Application\Http\Middleware\AuthenticateApiRequest;
use Application\Models\Identity\Identity;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route as RouteFacade;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;
use Source\Identity\Application\Exception\InvalidInternalApiTokenConfigurationException;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Source\Identity\Application\Service\InternalApiTokenServiceInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Tests\Helper\CreateApiAuthenticationContext;
use Tests\TestCase;

class AuthenticateApiRequestTest extends TestCase
{
    public function testVerifiedBearerSetsIdentityAndPassesTheVerifiedEnvelopeWithoutSessionLogin(): void
    {
        $context = CreateApiAuthenticationContext::create();
        $request = Request::create('/api/v1/wiki/wikis/ja', 'GET', server: ['HTTP_AUTHORIZATION' => 'Bearer internal-token']);
        $request->setRouteResolver(static fn () => new Route('GET', 'api/v1/wiki/wikis/{language}', static fn () => null));
        /** @var InternalApiTokenServiceInterface&MockInterface $service */
        $service = Mockery::mock(InternalApiTokenServiceInterface::class);
        $service->shouldReceive('verify')->once()->with('internal-token')->andReturn($context);
        Auth::shouldReceive('onceUsingId')->once()->with((string) $context->identityIdentifier())->andReturn(new Identity());
        Auth::shouldReceive('forgetGuards')->once();
        $response = (new AuthenticateApiRequest($service))->handle($request, static function (Request $request) use ($context) {
            self::assertSame($context, $request->attributes->get(ApiAuthenticationContext::class));
            self::assertFalse($request->hasSession());

            return response('bearer');
        });
        self::assertSame('bearer', $response->getContent());
    }

    public function testCookieRequestIsUnchangedAndStaleBearerEnvelopeIsRemoved(): void
    {
        $request = Request::create('/api/v1/identity/auth/me');
        $request->attributes->set(ApiAuthenticationContext::class, CreateApiAuthenticationContext::create());
        /** @var InternalApiTokenServiceInterface&MockInterface $service */
        $service = Mockery::mock(InternalApiTokenServiceInterface::class);
        $service->shouldNotReceive('verify');
        $response = (new AuthenticateApiRequest($service))->handle($request, static function (Request $request) {
            self::assertNull($request->attributes->get(ApiAuthenticationContext::class));

            return response('cookie');
        });
        self::assertSame('cookie', $response->getContent());
    }

    public function testInvalidBearerIs401EvenWithCookie(): void
    {
        $request = $this->bearerRequest('GET', 'api/v1/identity/auth/me');
        $request->cookies->set('k-pool_session', 'valid-cookie');
        /** @var InternalApiTokenServiceInterface&MockInterface $service */
        $service = Mockery::mock(InternalApiTokenServiceInterface::class);
        $service->shouldReceive('verify')->once()->andThrow(new InvalidAuthorizationTokenException());
        $this->expectException(UnauthorizedHttpException::class);
        (new AuthenticateApiRequest($service))->handle($request, static fn () => response('must not run'));
    }

    public function testConfigurationFailureIsNotConvertedTo401(): void
    {
        $request = $this->bearerRequest('GET', 'api/v1/identity/auth/me');
        /** @var InternalApiTokenServiceInterface&MockInterface $service */
        $service = Mockery::mock(InternalApiTokenServiceInterface::class);
        $service->shouldReceive('verify')->once()->andThrow(new InvalidInternalApiTokenConfigurationException());
        $this->expectException(InvalidInternalApiTokenConfigurationException::class);
        (new AuthenticateApiRequest($service))->handle($request, static fn () => response('must not run'));
    }

    /** @return array<string, array{string, string, AuthorizationScope|null}> */
    public static function allowedRoutes(): array
    {
        return [
            'account choices' => ['GET', 'api/v1/identity/auth/me', null],
            'account switch' => ['POST', 'api/v1/account/accounts/switch', null],
            'public list' => ['GET', 'api/v1/wiki/wikis/{language}', AuthorizationScope::WikiRead],
            'create' => ['POST', 'api/v1/wiki/wiki/create', AuthorizationScope::DraftWrite],
            'edit' => ['POST', 'api/v1/wiki/wiki/{wikiId}/edit', AuthorizationScope::DraftWrite],
            'submit batch' => ['POST', 'api/v1/wiki/wiki/submit', AuthorizationScope::DraftSubmit],
            'submit' => ['POST', 'api/v1/wiki/wiki/{wikiId}/submit', AuthorizationScope::DraftSubmit],
            'draft list' => ['GET', 'api/v1/wiki/my/draft-wikis', AuthorizationScope::DraftRead],
            'all draft list' => ['GET', 'api/v1/wiki/draft-wikis', AuthorizationScope::DraftRead],
            'masters' => ['GET', 'api/v1/wiki/wikis/{language}/masters', AuthorizationScope::DraftRead],
            'translation masters' => ['GET', 'api/v1/wiki/wiki-translation-sets/masters', AuthorizationScope::DraftRead],
            'related profiles' => ['GET', 'api/v1/wiki/wiki/{language}/{slug}/related-profiles', AuthorizationScope::WikiRead],
            'agency published' => ['GET', 'api/v1/wiki/wiki/{language}/agency/{slug}', AuthorizationScope::WikiRead],
            'agency draft' => ['GET', 'api/v1/wiki/wiki/agency/{wikiIdentifier}/draft', AuthorizationScope::DraftRead],
            'agency my draft' => ['GET', 'api/v1/wiki/wiki/{language}/agency/{slug}/my/draft', AuthorizationScope::DraftRead],
            'group published' => ['GET', 'api/v1/wiki/wiki/{language}/group/{slug}', AuthorizationScope::WikiRead],
            'group draft' => ['GET', 'api/v1/wiki/wiki/group/{wikiIdentifier}/draft', AuthorizationScope::DraftRead],
            'group my draft' => ['GET', 'api/v1/wiki/wiki/{language}/group/{slug}/my/draft', AuthorizationScope::DraftRead],
            'song published' => ['GET', 'api/v1/wiki/wiki/{language}/song/{slug}', AuthorizationScope::WikiRead],
            'song draft' => ['GET', 'api/v1/wiki/wiki/song/{wikiIdentifier}/draft', AuthorizationScope::DraftRead],
            'song my draft' => ['GET', 'api/v1/wiki/wiki/{language}/song/{slug}/my/draft', AuthorizationScope::DraftRead],
            'talent published' => ['GET', 'api/v1/wiki/wiki/{language}/talent/{slug}', AuthorizationScope::WikiRead],
            'talent draft' => ['GET', 'api/v1/wiki/wiki/talent/{wikiIdentifier}/draft', AuthorizationScope::DraftRead],
            'talent my draft' => ['GET', 'api/v1/wiki/wiki/{language}/talent/{slug}/my/draft', AuthorizationScope::DraftRead],
            'draft images' => ['GET', 'api/v1/wiki/draft-images', AuthorizationScope::ImageRead],
            'managed images' => ['GET', 'api/v1/wiki/managed-images', AuthorizationScope::ImageRead],
            'uploaded images' => ['GET', 'api/v1/wiki/images', AuthorizationScope::ImageRead],
            'upload' => ['POST', 'api/v1/wiki/image/upload', AuthorizationScope::ImageWrite],
            'metadata' => ['PUT', 'api/v1/wiki/image/{imageId}/metadata', AuthorizationScope::ImageWrite],
        ];
    }

    #[DataProvider('allowedRoutes')]
    public function testScopeMapping(string $method, string $uri, ?AuthorizationScope $scope): void
    {
        foreach (['identity', 'account', 'wiki'] as $prefix) {
            RouteFacade::prefix('api/v1/' . $prefix)->group(__DIR__ . '/../../../routes/v1/' . $prefix . '_api.php');
        }
        $path = preg_replace('/\{[^}]+\}/', 'example', $uri);
        self::assertIsString($path);
        $actual = RouteFacade::getRoutes()->match(Request::create('/' . $path, $method));
        self::assertSame($uri, $actual->uri());
        $request = $this->bearerRequest($method, $uri);
        $context = CreateApiAuthenticationContext::create([$scope ?? AuthorizationScope::WikiRead]);
        /** @var InternalApiTokenServiceInterface&MockInterface $service */
        $service = Mockery::mock(InternalApiTokenServiceInterface::class);
        $service->shouldReceive('verify')->once()->andReturn($context);
        Auth::shouldReceive('onceUsingId')->once()->with((string) $context->identityIdentifier())->andReturn(new Identity());
        Auth::shouldReceive('forgetGuards')->once();
        $response = (new AuthenticateApiRequest($service))->handle($request, static fn () => response('ok'));
        self::assertSame(200, $response->getStatusCode());
        self::assertNull($request->attributes->get(ApiAuthenticationContext::class));
    }

    #[DataProvider('allowedRoutes')]
    public function testMissingScopeIs403(string $method, string $uri, ?AuthorizationScope $scope): void
    {
        if ($scope === null) {
            $this->expectNotToPerformAssertions();

            return;
        }
        $request = $this->bearerRequest($method, $uri);
        $wrongScope = $scope === AuthorizationScope::WikiRead ? AuthorizationScope::DraftWrite : AuthorizationScope::WikiRead;
        /** @var InternalApiTokenServiceInterface&MockInterface $service */
        $service = Mockery::mock(InternalApiTokenServiceInterface::class);
        $service->shouldReceive('verify')->once()->andReturn(CreateApiAuthenticationContext::create([$wrongScope]));
        Auth::shouldReceive('onceUsingId')->never();

        try {
            (new AuthenticateApiRequest($service))->handle($request, static fn () => response('must not run'));
            self::fail('Missing scope must be rejected.');
        } catch (ForbiddenHttpException $exception) {
            self::assertSame(403, $exception->getHttpStatus());
            self::assertSame('insufficient_scope', $exception->toProblemDetails()['code']);
        }
    }

    /** @return array<string, array{string, string}> */
    public static function forbiddenRoutes(): array
    {
        return [
            'auto create' => ['POST', 'api/v1/wiki/wiki/auto-create'],
            'delete identity' => ['DELETE', 'api/v1/identity/identities/me'],
            'passkeys' => ['GET', 'api/v1/identity/auth/passkeys'],
            'logout' => ['POST', 'api/v1/identity/auth/logout'],
            'consent create' => ['POST', 'api/v1/identity/oauth/requests'],
            'consent detail' => ['GET', 'api/v1/identity/oauth/requests/{requestIdentifier}'],
            'consent approve' => ['POST', 'api/v1/identity/oauth/requests/{requestIdentifier}/approve'],
            'consent deny' => ['POST', 'api/v1/identity/oauth/requests/{requestIdentifier}/deny'],
            'publish' => ['POST', 'api/v1/wiki/wiki/{wikiId}/publish'],
            'wrong method' => ['DELETE', 'api/v1/wiki/wiki/create'],
            'metadata old method' => ['PATCH', 'api/v1/wiki/image/{imageId}/metadata'],
            'unknown' => ['GET', 'api/v1/future'],
        ];
    }

    #[DataProvider('forbiddenRoutes')]
    public function testUnregisteredRoutesAre403EvenWithAllScopes(string $method, string $uri): void
    {
        $request = $this->bearerRequest($method, $uri);
        /** @var InternalApiTokenServiceInterface&MockInterface $service */
        $service = Mockery::mock(InternalApiTokenServiceInterface::class);
        $service->shouldReceive('verify')->once()->andReturn(CreateApiAuthenticationContext::create(AuthorizationScope::cases()));
        Auth::shouldReceive('onceUsingId')->never();

        try {
            (new AuthenticateApiRequest($service))->handle($request, static fn () => response('must not run'));
            self::fail('Only explicitly registered routes can accept Bearer.');
        } catch (ForbiddenHttpException $exception) {
            self::assertSame('oauth_route_not_allowed', $exception->toProblemDetails()['code']);
        }
    }

    public function testDownstreamBusinessAuthorizationFailurePropagatesAndClearsBearerState(): void
    {
        $request = $this->bearerRequest('POST', 'api/v1/wiki/wiki/{wikiId}/edit');
        /** @var InternalApiTokenServiceInterface&MockInterface $service */
        $service = Mockery::mock(InternalApiTokenServiceInterface::class);
        $service->shouldReceive('verify')->once()->andReturn(CreateApiAuthenticationContext::create([AuthorizationScope::DraftWrite]));
        Auth::shouldReceive('onceUsingId')->once()->andReturn(new Identity());
        Auth::shouldReceive('forgetGuards')->once();

        try {
            (new AuthenticateApiRequest($service))->handle($request, static function (): never {
                throw new ForbiddenHttpException(extensions: ['code' => 'business_denied']);
            });
        } catch (ForbiddenHttpException $exception) {
            self::assertSame('business_denied', $exception->toProblemDetails()['code']);
        }
        self::assertNull($request->attributes->get(ApiAuthenticationContext::class));
    }

    private function bearerRequest(string $method, string $uri): Request
    {
        $request = Request::create('/' . $uri, $method, server: ['HTTP_AUTHORIZATION' => 'Bearer internal-token']);
        $request->setRouteResolver(static fn () => new Route($method, $uri, static fn () => null));

        return $request;
    }

    /** @return array<string, array{string}> */
    public static function malformedHeaders(): array
    {
        return ['empty' => [''], 'empty bearer' => ['Bearer '], 'basic' => ['Basic secret'],
            'multiple tokens' => ['Bearer first, Bearer second'], 'whitespace' => ['Bearer one two'],
            'leading space' => [' Bearer one'], 'no scheme' => ['token']];
    }

    #[DataProvider('malformedHeaders')]
    public function testMalformedAuthorizationDoesNotReachTheVerifier(string $header): void
    {
        $request = $this->bearerRequest('GET', 'api/v1/identity/auth/me');
        $request->headers->set('Authorization', $header);
        /** @var InternalApiTokenServiceInterface&MockInterface $service */
        $service = Mockery::mock(InternalApiTokenServiceInterface::class);
        $service->shouldNotReceive('verify');
        $this->expectException(UnauthorizedHttpException::class);
        (new AuthenticateApiRequest($service))->handle($request, static fn () => response('must not run'));
    }

    public function testMissingIdentityIs401WithoutStartingASession(): void
    {
        $request = $this->bearerRequest('GET', 'api/v1/identity/auth/me');
        /** @var InternalApiTokenServiceInterface&MockInterface $service */
        $service = Mockery::mock(InternalApiTokenServiceInterface::class);
        $service->shouldReceive('verify')->once()->andReturn(CreateApiAuthenticationContext::create());
        Auth::shouldReceive('onceUsingId')->once()->andReturn(false);
        $this->expectException(UnauthorizedHttpException::class);
        (new AuthenticateApiRequest($service))->handle($request, static fn () => response('must not run'));
    }

    public function testAuthorizationHeaderNeverFallsBackToCookieAuthentication(): void
    {
        $request = Request::create('/api/v1/wiki/wikis/ja', 'GET', server: ['HTTP_AUTHORIZATION' => 'Bearer ']);
        $request->cookies->set('k-pool_session', 'valid-cookie');
        /** @var InternalApiTokenServiceInterface&MockInterface $service */
        $service = Mockery::mock(InternalApiTokenServiceInterface::class);
        $service->shouldNotReceive('verify');
        $middleware = new AuthenticateApiRequest($service);
        $this->expectException(UnauthorizedHttpException::class);
        $middleware->handle($request, static function (): never {
            self::fail('Cookie fallback must not run.');
        });
    }
}
