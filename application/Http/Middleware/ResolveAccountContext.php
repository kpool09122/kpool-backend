<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Context\AccountContext;
use Application\Http\Context\AccountResolver;
use Application\Http\Context\ActorContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveAccountContext
{
    public function __construct(private readonly AccountResolver $accountResolver)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        /** @var ActorContext $actorContext */
        $actorContext = app(ActorContext::class);
        app()->instance(AccountContext::class, $this->accountResolver->resolve($actorContext->identityIdentifier));

        return $next($request);
    }
}
