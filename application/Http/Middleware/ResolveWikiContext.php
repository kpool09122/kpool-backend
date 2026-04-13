<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

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
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        /** @var ActorContext $actorContext */
        $actorContext = app(ActorContext::class);

        $wikiContext = new WikiContext(
            principalIdentifier: $this->principalResolver->resolve($actorContext->identityIdentifier),
        );

        app()->instance(WikiContext::class, $wikiContext);

        return $next($request);
    }
}
