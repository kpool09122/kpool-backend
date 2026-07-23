<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Context\ActorContext;
use Application\Http\Context\AuthContextCache;
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
    public function __construct(
        private readonly AuthContextCache $cache,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Identity $identity */
        $identity = Auth::user();

        $identityIdentifier = new IdentityIdentifier($identity->id);
        $actorContext = $this->cache->resolveActor(
            $identityIdentifier,
            function () use ($identity, $identityIdentifier): ActorContext {
                $language = Language::from($identity->language);

                return new ActorContext(
                    identityIdentifier: $identityIdentifier,
                    language: $language,
                    delegationIdentifier: $identity->delegation_identifier !== null
                        ? new DelegationIdentifier($identity->delegation_identifier)
                        : null,
                    originalIdentityIdentifier: $identity->original_identity_identifier !== null
                        ? new IdentityIdentifier($identity->original_identity_identifier)
                        : null,
                );
            },
        );

        app()->instance(ActorContext::class, $actorContext);
        app()->setLocale($actorContext->language->value);

        return $next($request);
    }
}
