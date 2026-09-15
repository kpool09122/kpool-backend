<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Context\AccountContext;
use Application\Http\Context\AccountResolver;
use Application\Http\Context\ActorContext;
use Application\Http\Context\PrincipalResolver;
use Application\Http\Context\WikiContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveWikiContext
{
    public function __construct(
        private readonly PrincipalResolver $principalResolver,
        private readonly AccountResolver $accountResolver,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        /** @var ActorContext $actorContext */
        $actorContext = app(ActorContext::class);
        /** @var AccountContext $accountContext */
        $accountContext = app()->bound(AccountContext::class)
            ? app(AccountContext::class)
            : $this->accountResolver->resolve($actorContext->identityIdentifier);
        app()->instance(AccountContext::class, $accountContext);
        app()->instance(WikiContext::class, new WikiContext($this->principalResolver->resolve($accountContext)));

        return $next($request);
    }
}
