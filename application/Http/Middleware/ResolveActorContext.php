<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Context\ActorContext;
use Application\Models\Identity\Identity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Symfony\Component\HttpFoundation\Response;

class ResolveActorContext
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Identity $identity */
        $identity = Auth::user();

        $language = Language::from($identity->language);

        $actorContext = new ActorContext(
            identityIdentifier: new IdentityIdentifier($identity->id),
            language: $language,
            delegationIdentifier: $identity->delegation_identifier !== null
                ? new DelegationIdentifier($identity->delegation_identifier)
                : null,
            originalIdentityIdentifier: $identity->original_identity_identifier !== null
                ? new IdentityIdentifier($identity->original_identity_identifier)
                : null,
        );

        app()->instance(ActorContext::class, $actorContext);
        app()->setLocale($language->value);

        return $next($request);
    }
}
