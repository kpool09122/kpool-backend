<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Context\AccountContext;
use Application\Http\Context\AccountResolver;
use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Models\Account\Principal as AccountPrincipal;
use Application\Models\SiteManagement\Principal as SiteManagementPrincipal;
use Application\Models\Wiki\Principal as WikiPrincipal;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;
use TypeError;
use ValueError;

/** Status is deliberately read from the authoritative store, never from an authorization cache. */
readonly class EnsurePrincipalActive
{
    public function __construct(private AccountResolver $accountResolver)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->path(), [
            'api/v1/identity/auth/me', 'api/v1/identity/auth/logout', 'api/v1/wiki/principal/me',
        ], true)) {
            return $next($request);
        }

        try {
            $context = $this->accountResolver->resolve(new IdentityIdentifier((string) Auth::id()));
        } catch (AccountNotFoundException) {
            if ($request->path() === 'api/v1/account/accounts/setup') {
                return $next($request);
            }
            $this->deny();
        } catch (ValueError|TypeError) {
            $this->deny();
        }
        $returning = $request->isMethod('POST') && $request->path() === 'api/v1/account/accounts/switch'
            && $request->input('delegationIdentifier') === null;
        $identifiers = [(string) $context->originalPrincipalIdentifier()];
        if (! $returning) {
            $identifiers[] = (string) $context->principal()->principalIdentifier();
        }
        foreach (array_unique($identifiers) as $identifier) {
            $principal = AccountPrincipal::query()->find($identifier);
            if ($principal === null || $principal->status !== 'active') {
                $this->deny();
            }
        }
        if ($request->is('api/v1/wiki/*') || $request->is('api/v1/site-management/*')) {
            $principal = $request->is('api/v1/wiki/*')
                ? WikiPrincipal::query()->where('identity_id', (string) Auth::id())->where('account_id', (string) $context->principal()->accountIdentifier())->first()
                : SiteManagementPrincipal::query()->where('identity_id', (string) Auth::id())->where('account_id', (string) $context->principal()->accountIdentifier())->first();
            if ($principal !== null && $principal->status !== 'active') {
                $this->deny();
            }
        }

        $request->attributes->set(AccountContext::class, $context);

        return $next($request);
    }

    private function deny(): never
    {
        throw new ForbiddenHttpException(detail: error_message('principal_inactive', app()->bound(ActorContext::class) ? app(ActorContext::class)->language->value : app()->getLocale()), extensions: ['code' => 'principal_inactive']);
    }
}
