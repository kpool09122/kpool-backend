<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Context\AccountResolver;
use Application\Http\Context\AuthContextCache;
use Application\Http\Exceptions\ForbiddenHttpException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Delegation\Application\Exception\DelegationUnavailableException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;

readonly class EnsureAccountActive
{
    private const array EXEMPT_PATHS = [
        'api/identity/auth/me',
        'api/identity/auth/logout',
        'api/account/accounts/setup',
    ];

    public function __construct(
        private AuthContextCache $authContextCache,
        private AccountResolver $accountResolver,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->path(), self::EXEMPT_PATHS, true)) {
            return $next($request);
        }

        try {
            $identityIdentifier = new IdentityIdentifier((string) Auth::id());
            $context = $this->authContextCache->resolveAccount(
                $identityIdentifier,
                fn () => $this->accountResolver->resolve($identityIdentifier),
            );
        } catch (DelegationUnavailableException $e) {
            throw new ForbiddenHttpException(
                detail: 'The selected account is unavailable.',
                extensions: ['code' => 'account_unavailable'],
                previous: $e,
            );
        } catch (AccountNotFoundException $e) {
            throw new ForbiddenHttpException(
                detail: 'An account is required to use this API.',
                extensions: ['code' => 'account_required'],
                previous: $e,
            );
        }

        if ($context->originalAccountStatus() === AccountStatus::PENDING) {
            throw new ForbiddenHttpException(
                detail: 'Account initial setup is required.',
                extensions: ['code' => 'account_setup_required'],
            );
        }

        if ($context->originalAccountStatus() !== AccountStatus::ACTIVE
            || ($context->accountStatus() !== AccountStatus::ACTIVE && ! $this->isReturningToOriginalAccount($request))) {
            throw new ForbiddenHttpException(
                detail: 'The account is suspended.',
                extensions: ['code' => 'account_suspended'],
            );
        }

        return $next($request);
    }

    private function isReturningToOriginalAccount(Request $request): bool
    {
        return $request->isMethod('POST')
            && $request->path() === 'api/account/accounts/switch'
            && $request->input('delegationIdentifier') === null;
    }
}
