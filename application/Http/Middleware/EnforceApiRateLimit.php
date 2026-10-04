<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Context\AccountContext;
use Application\Http\Context\AccountResolver;
use Application\Http\Context\AuthContextCache;
use Application\Http\RateLimit\ApiRateLimiter;
use Application\Http\RateLimit\RateLimitOperation;
use Application\Http\RateLimit\RateLimitSubject;
use Application\Http\RateLimit\RateLimitSurface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;

readonly class EnforceApiRateLimit
{
    public function __construct(
        private ApiRateLimiter $apiRateLimiter,
        private AuthServiceInterface $authService,
        private AccountResolver $accountResolver,
        private AuthContextCache $authContextCache,
    ) {
    }

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next, string $surface, string $operation): Response
    {
        $this->apiRateLimiter->hit(
            RateLimitSurface::from($surface),
            RateLimitOperation::from($operation),
            $this->resolveSubject($request),
        );

        return $next($request);
    }

    private function resolveSubject(Request $request): RateLimitSubject
    {
        if (! Auth::check()) {
            return RateLimitSubject::ip($request->ip() ?? 'unknown');
        }

        $identityIdentifier = new IdentityIdentifier((string) Auth::id());
        if (! $this->authService->isCurrentSessionValid($identityIdentifier)) {
            $this->authService->logout();

            return RateLimitSubject::ip($request->ip() ?? 'unknown');
        }

        if (! app()->bound(AccountContext::class)) {
            app()->instance(AccountContext::class, $this->authContextCache->resolveAccount(
                $identityIdentifier,
                fn () => $this->accountResolver->resolve($identityIdentifier),
            ));
        }

        return RateLimitSubject::account((string) app(AccountContext::class)->principal()->accountIdentifier());
    }
}
