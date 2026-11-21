<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Context\AccountContext;
use Application\Http\Context\ActorContext;
use Application\Http\Context\AuthContextCache;
use Application\Http\Context\MonetizationContext;
use Application\Http\Context\MonetizationPrincipalResolver;
use Application\Http\Exceptions\NotFoundHttpException;
use Closure;
use Illuminate\Http\Request;
use Source\Monetization\Shared\Domain\Exception\PrincipalNotFoundException;
use Symfony\Component\HttpFoundation\Response;

readonly class ResolveMonetizationContext
{
    public function __construct(
        private MonetizationPrincipalResolver $monetizationPrincipalResolver,
        private AuthContextCache $authContextCache,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $accountContext = app(AccountContext::class);

        try {
            $context = $this->authContextCache->resolveMonetization(
                $accountContext->originalIdentityIdentifier(),
                $accountContext->principal()->accountIdentifier(),
                fn () => new MonetizationContext($this->monetizationPrincipalResolver->resolve($accountContext)),
            );
        } catch (PrincipalNotFoundException $e) {
            throw new NotFoundHttpException(detail: error_message('principal_not_found', app(ActorContext::class)->language->value), previous: $e);
        }
        app()->instance(MonetizationContext::class, $context);

        return $next($request);
    }
}
