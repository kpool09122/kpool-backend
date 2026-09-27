<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Context\AccountContext;
use Application\Http\Context\AccountResolver;
use Application\Http\Context\ActorContext;
use Application\Http\Context\AuthContextCache;
use Closure;
use Illuminate\Http\Request;
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
        /** @var ActorContext $actorContext */
        $actorContext = app(ActorContext::class);
        app()->instance(AccountContext::class, $this->authContextCache->resolveAccount(
            $actorContext->identityIdentifier,
            fn () => $this->accountResolver->resolve($actorContext->identityIdentifier),
        ));

        return $next($request);
    }
}
