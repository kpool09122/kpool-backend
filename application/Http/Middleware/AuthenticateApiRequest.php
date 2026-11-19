<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\UnauthorizedHttpException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Source\Identity\Application\Service\InternalApiTokenServiceInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Symfony\Component\HttpFoundation\Response;

readonly class AuthenticateApiRequest
{
    /** @var array<string, AuthorizationScope|null> */
    private const array ROUTES = [
        'GET api/v1/identity/auth/me' => null,
        'POST api/v1/account/accounts/switch' => null,
        'GET api/v1/wiki/wikis/{language}' => AuthorizationScope::WikiRead,
        'GET api/v1/wiki/wiki/{language}/agency/{slug}' => AuthorizationScope::WikiRead,
        'GET api/v1/wiki/wiki/{language}/group/{slug}' => AuthorizationScope::WikiRead,
        'GET api/v1/wiki/wiki/{language}/song/{slug}' => AuthorizationScope::WikiRead,
        'GET api/v1/wiki/wiki/{language}/talent/{slug}' => AuthorizationScope::WikiRead,
        'GET api/v1/wiki/wiki/{language}/{slug}/related-profiles' => AuthorizationScope::WikiRead,
        'GET api/v1/wiki/wikis/{language}/masters' => AuthorizationScope::DraftRead,
        'GET api/v1/wiki/wiki-translation-sets/masters' => AuthorizationScope::DraftRead,
        'GET api/v1/wiki/my/draft-wikis' => AuthorizationScope::DraftRead,
        'GET api/v1/wiki/draft-wikis' => AuthorizationScope::DraftRead,
        'GET api/v1/wiki/wiki/agency/{wikiIdentifier}/draft' => AuthorizationScope::DraftRead,
        'GET api/v1/wiki/wiki/group/{wikiIdentifier}/draft' => AuthorizationScope::DraftRead,
        'GET api/v1/wiki/wiki/song/{wikiIdentifier}/draft' => AuthorizationScope::DraftRead,
        'GET api/v1/wiki/wiki/talent/{wikiIdentifier}/draft' => AuthorizationScope::DraftRead,
        'GET api/v1/wiki/wiki/{language}/agency/{slug}/my/draft' => AuthorizationScope::DraftRead,
        'GET api/v1/wiki/wiki/{language}/group/{slug}/my/draft' => AuthorizationScope::DraftRead,
        'GET api/v1/wiki/wiki/{language}/song/{slug}/my/draft' => AuthorizationScope::DraftRead,
        'GET api/v1/wiki/wiki/{language}/talent/{slug}/my/draft' => AuthorizationScope::DraftRead,
        'POST api/v1/wiki/wiki/create' => AuthorizationScope::DraftWrite,
        'POST api/v1/wiki/wiki/{wikiId}/edit' => AuthorizationScope::DraftWrite,
        'POST api/v1/wiki/wiki/submit' => AuthorizationScope::DraftSubmit,
        'POST api/v1/wiki/wiki/{wikiId}/submit' => AuthorizationScope::DraftSubmit,
        'GET api/v1/wiki/images' => AuthorizationScope::ImageRead,
        'GET api/v1/wiki/draft-images' => AuthorizationScope::ImageRead,
        'GET api/v1/wiki/managed-images' => AuthorizationScope::ImageRead,
        'POST api/v1/wiki/image/upload' => AuthorizationScope::ImageWrite,
        'PUT api/v1/wiki/image/{imageId}/metadata' => AuthorizationScope::ImageWrite,
    ];

    public function __construct(private InternalApiTokenServiceInterface $internalApiTokenService)
    {
    }

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        // Clear request-local state even when a Request object is reused by a long-lived worker.
        $request->attributes->remove(ApiAuthenticationContext::class);
        if (! $request->headers->has('Authorization')) {
            return $next($request);
        }

        $headers = $request->headers->all('Authorization');
        if (count($headers) !== 1 || preg_match('/\ABearer ([A-Za-z0-9._~+\/-]+=*)\z/iD', $headers[0], $matches) !== 1) {
            throw $this->unauthorized($request);
        }

        try {
            $context = $this->internalApiTokenService->verify($matches[1]);
        } catch (InvalidAuthorizationTokenException) {
            throw $this->unauthorized($request);
        }

        $route = $request->route();
        $key = $route instanceof Route ? $request->method() . ' ' . $route->uri() : '';
        if (! array_key_exists($key, self::ROUTES)) {
            throw $this->forbidden($request, 'oauth_route_not_allowed');
        }
        $scope = self::ROUTES[$key];
        if ($scope !== null && ! in_array($scope, $context->scopes(), true)) {
            throw $this->forbidden($request, 'insufficient_scope');
        }

        if (Auth::onceUsingId((string) $context->identityIdentifier()) === false) {
            throw $this->unauthorized($request);
        }
        $request->attributes->set(ApiAuthenticationContext::class, $context);

        try {
            return $next($request);
        } finally {
            $request->attributes->remove(ApiAuthenticationContext::class);
            Auth::forgetGuards();
        }
    }

    private function unauthorized(Request $request): UnauthorizedHttpException
    {
        return new UnauthorizedHttpException(
            detail: error_message('unauthorized', $request->header('Accept-Language', 'en')),
            extensions: ['code' => 'authentication_required'],
        );
    }

    private function forbidden(Request $request, string $code): ForbiddenHttpException
    {
        return new ForbiddenHttpException(
            detail: error_message('unauthorized', $request->header('Accept-Language', 'en')),
            extensions: ['code' => $code],
        );
    }
}
