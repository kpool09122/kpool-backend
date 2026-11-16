<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Context\AccountContext;
use Application\Http\Context\AccountResolver;
use Application\Http\Context\ActorContext;
use Application\Http\Context\AuthContextCache;
use Closure;
use Illuminate\Http\Request;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Symfony\Component\HttpFoundation\Response;

class ResolveAccountContext
{
    public function __construct(
        private readonly AccountResolver $accountResolver,
        private readonly AuthContextCache $authContextCache,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->attributes->get(ApiAuthenticationContext::class) instanceof ApiAuthenticationContext
            && $request->isMethod('POST') && $request->path() === 'api/v1/account/accounts/switch') {
            return $next($request);
        }
        /** @var ActorContext $actorContext */
        $actorContext = app(ActorContext::class);
        $verifiedContext = $request->attributes->get(AccountContext::class);
        app()->instance(AccountContext::class, $verifiedContext instanceof AccountContext
            ? $verifiedContext
            : $this->authContextCache->resolveAccount(
                $actorContext->identityIdentifier,
                fn () => $this->accountResolver->resolve($actorContext->identityIdentifier),
            ));

        return $next($request);
    }
}
