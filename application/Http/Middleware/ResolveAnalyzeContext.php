<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Context\AccountContext;
use Application\Http\Context\ActorContext;
use Application\Http\Context\AnalyzeContext;
use Application\Http\Context\AnalyzePrincipalResolver;
use Application\Http\Exceptions\ForbiddenHttpException;
use Closure;
use Illuminate\Http\Request;
use Source\Analyze\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

readonly class ResolveAnalyzeContext
{
    public function __construct(private AnalyzePrincipalResolver $analyzePrincipalResolver)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $actorContext = app(ActorContext::class);
        $accountContext = app(AccountContext::class);

        try {
            app()->instance(AnalyzeContext::class, new AnalyzeContext($this->analyzePrincipalResolver->resolve($actorContext->identityIdentifier, $accountContext->principal()->accountIdentifier())));
        } catch (UnauthorizedException $e) {
            throw new ForbiddenHttpException(detail: error_message('unauthorized', $actorContext->language->value), previous: $e);
        }

        return $next($request);
    }
}
