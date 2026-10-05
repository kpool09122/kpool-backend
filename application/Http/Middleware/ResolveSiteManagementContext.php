<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Context\ActorContext;
use Application\Http\Context\SiteManagementContext;
use Application\Http\Context\SiteManagementPrincipalResolver;
use Application\Http\Exceptions\ForbiddenHttpException;
use Closure;
use Illuminate\Http\Request;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

readonly class ResolveSiteManagementContext
{
    public function __construct(private SiteManagementPrincipalResolver $siteManagementPrincipalResolver)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $actorContext = app(ActorContext::class);

        try {
            app()->instance(SiteManagementContext::class, new SiteManagementContext($this->siteManagementPrincipalResolver->resolve($actorContext)));
        } catch (UnauthorizedException $e) {
            throw new ForbiddenHttpException(detail: error_message('unauthorized', $actorContext->language->value), previous: $e);
        }

        return $next($request);
    }
}
