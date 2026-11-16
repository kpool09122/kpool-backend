<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Context\AccountContext;
use Application\Http\Context\AccountResolver;
use Application\Http\Exceptions\ForbiddenHttpException;
use Closure;
use Illuminate\Http\Request;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Delegation\Application\Exception\DelegationUnavailableException;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Symfony\Component\HttpFoundation\Response;

readonly class AuthorizeApiAccount
{
    public function __construct(private AccountResolver $accountResolver)
    {
    }

    public static function isSelectionRequest(Request $request): bool
    {
        return ($request->isMethod('GET') && $request->path() === 'api/v1/identity/auth/me')
            || ($request->isMethod('POST') && $request->path() === 'api/v1/account/accounts/switch');
    }

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $authorization = $request->attributes->get(ApiAuthenticationContext::class);
        if (! $authorization instanceof ApiAuthenticationContext
            || self::isSelectionRequest($request)) {
            return $next($request);
        }

        try {
            $context = $this->accountResolver->resolve($authorization->identityIdentifier());
        } catch (AccountNotFoundException|DelegationUnavailableException $exception) {
            throw new ForbiddenHttpException(
                detail: error_message('disallowed_delegation_operation', $request->header('Accept-Language', 'en')),
                extensions: ['code' => 'oauth_account_not_allowed'],
                previous: $exception,
            );
        }
        $request->attributes->set(AccountContext::class, $context);

        return $next($request);
    }
}
